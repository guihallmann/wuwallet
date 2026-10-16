<?php

declare(strict_types=1);

namespace App\Services\Brapi;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Sleep;

class BrapiClient
{
    private const TICKERS_PATH = '/api/v2/tickers';

    private const MAX_BACKOFF_SECONDS = 30;

    private const DEFAULT_RETRY_AFTER_SECONDS = 60;

    /**
     * Fetches every page of the tickers endpoint.
     *
     * @param  array<string, scalar>  $filters
     * @return list<array<string, mixed>>
     *
     * @throws BrapiException
     */
    public function tickers(array $filters = []): array
    {
        $pageSize = (int) config('services.brapi.page_size');
        $maxPages = (int) config('services.brapi.max_pages');
        $throttleMs = $this->hasToken() ? 0 : (int) config('services.brapi.page_delay_ms');

        $results = [];
        $page = 1;

        do {
            $body = $this->get(self::TICKERS_PATH, [...$filters, 'page' => $page, 'limit' => $pageSize]);

            $items = $body['results'] ?? null;

            if (! is_array($items)) {
                throw new BrapiException('Unexpected brapi response: missing "results".');
            }

            foreach ($items as $item) {
                if (is_array($item)) {
                    /** @var array<string, mixed> $item */
                    $results[] = $item;
                }
            }

            $pagination = $body['pagination'] ?? [];
            $hasNext = is_array($pagination) && ($pagination['hasNextPage'] ?? false) === true;

            if ($hasNext && $page >= $maxPages) {
                throw new BrapiException("brapi pagination exceeded {$maxPages} pages.");
            }

            if ($hasNext && $throttleMs > 0) {
                // Unauthenticated callers are limited to 20 requests per minute per IP.
                Sleep::for($throttleMs)->milliseconds();
            }

            $page++;
        } while ($hasNext);

        return $results;
    }

    /**
     * @param  array<string, scalar>  $query
     * @return array<string, mixed>
     *
     * @throws BrapiException
     */
    private function get(string $path, array $query): array
    {
        $maxAttempts = max(1, (int) config('services.brapi.max_attempts'));

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                $response = $this->request()->get($path, $query);
            } catch (ConnectionException $e) {
                if ($attempt === $maxAttempts) {
                    $this->logFailure($path, $attempt, null, null, $e->getMessage());

                    throw new BrapiException('brapi connection failed: ' . $e->getMessage(), previous: $e);
                }

                $this->logRetry($path, $attempt, null, null);
                $this->sleepBackoff($attempt);

                continue;
            }

            if ($response->successful()) {
                $this->warnIfQuotaLow($response);

                /** @var array<string, mixed> */
                return $response->json() ?? [];
            }

            $status = $response->status();
            $code = $this->errorCode($response);

            if (! $this->isRetryable($status) || $attempt === $maxAttempts) {
                $this->logFailure($path, $attempt, $status, $code, $this->errorMessage($response));

                throw new BrapiException(
                    "brapi request failed with status {$status}" . ($code !== null ? " ({$code})" : ''),
                    $status,
                    $code,
                );
            }

            $this->logRetry($path, $attempt, $status, $code);

            if ($status === 429) {
                $this->sleepRetryAfter($response);
            } else {
                $this->sleepBackoff($attempt);
            }
        }

        throw new BrapiException('brapi request failed.');
    }

    private function request(): PendingRequest
    {
        $request = Http::baseUrl((string) config('services.brapi.base_url'))
            ->acceptJson()
            ->timeout((int) config('services.brapi.timeout'))
            ->connectTimeout((int) config('services.brapi.connect_timeout'));

        if ($this->hasToken()) {
            $request = $request->withToken((string) config('services.brapi.key'));
        }

        return $request;
    }

    private function hasToken(): bool
    {
        $key = config('services.brapi.key');

        return is_string($key) && $key !== '';
    }

    private function isRetryable(int $status): bool
    {
        return $status === 429 || $status >= 500;
    }

    private function sleepRetryAfter(Response $response): void
    {
        $header = $response->header('Retry-After');
        $seconds = is_numeric($header) ? (int) $header : self::DEFAULT_RETRY_AFTER_SECONDS;
        $cap = (int) config('services.brapi.max_retry_after_seconds');

        Sleep::for(max(1, min($seconds, $cap)))->seconds();
    }

    private function sleepBackoff(int $attempt): void
    {
        $baseMs = min(2 ** $attempt, self::MAX_BACKOFF_SECONDS) * 1000;

        Sleep::for($baseMs + random_int(0, 500))->milliseconds();
    }

    private function errorCode(Response $response): ?string
    {
        $code = $response->json('code');

        return is_string($code) ? $code : null;
    }

    private function errorMessage(Response $response): ?string
    {
        $message = $response->json('message');

        return is_string($message) ? $message : null;
    }

    private function warnIfQuotaLow(Response $response): void
    {
        $remaining = $response->header('RateLimit-Remaining');

        if (is_numeric($remaining) && (int) $remaining < 100) {
            Log::warning('brapi quota is running low.', ['remaining' => (int) $remaining]);
        }
    }

    private function logRetry(string $path, int $attempt, ?int $status, ?string $code): void
    {
        Log::warning('brapi request failed, retrying.', [
            'path' => $path,
            'attempt' => $attempt,
            'status' => $status,
            'code' => $code,
        ]);
    }

    private function logFailure(string $path, int $attempt, ?int $status, ?string $code, ?string $message): void
    {
        Log::error('brapi request failed.', [
            'path' => $path,
            'attempt' => $attempt,
            'status' => $status,
            'code' => $code,
            'message' => $message,
        ]);
    }
}
