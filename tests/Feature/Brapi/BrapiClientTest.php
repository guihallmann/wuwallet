<?php

declare(strict_types=1);

namespace Tests\Feature\Brapi;

use App\Services\Brapi\BrapiClient;
use App\Services\Brapi\BrapiException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use Tests\TestCase;

class BrapiClientTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Sleep::fake();

        config([
            'services.brapi.base_url' => 'https://brapi.test',
            'services.brapi.key' => null,
            'services.brapi.max_attempts' => 3,
            'services.brapi.max_pages' => 3,
        ]);
    }

    public function test_it_follows_pagination_and_merges_results(): void
    {
        Http::fake([
            'brapi.test/*' => Http::sequence()
                ->push(['results' => [['symbol' => 'AAA3']], 'pagination' => ['hasNextPage' => true]])
                ->push(['results' => [['symbol' => 'BBB4']], 'pagination' => ['hasNextPage' => false]]),
        ]);

        $results = app(BrapiClient::class)->tickers(['type' => 'stock']);

        $this->assertSame(['AAA3', 'BBB4'], array_column($results, 'symbol'));
        Http::assertSentCount(2);
        Http::assertSent(fn (Request $r): bool => str_contains($r->url(), 'type=stock')
            && str_contains($r->url(), 'page=2')
            && str_contains($r->url(), 'limit=2000'));
    }

    public function test_it_throttles_between_pages_without_a_token(): void
    {
        Http::fake(['brapi.test/*' => $this->twoPages()]);

        app(BrapiClient::class)->tickers();

        Sleep::assertSleptTimes(1);
    }

    public function test_it_does_not_throttle_between_pages_with_a_token(): void
    {
        config(['services.brapi.key' => 'secret']);
        Http::fake(['brapi.test/*' => $this->twoPages()]);

        app(BrapiClient::class)->tickers();

        Sleep::assertNeverSlept();
    }

    private function twoPages(): mixed
    {
        return Http::sequence()
            ->push(['results' => [], 'pagination' => ['hasNextPage' => true]])
            ->push(['results' => [], 'pagination' => ['hasNextPage' => false]]);
    }

    public function test_it_sends_bearer_token_only_when_configured(): void
    {
        Http::fake(['brapi.test/*' => Http::response(['results' => [], 'pagination' => ['hasNextPage' => false]])]);

        app(BrapiClient::class)->tickers();
        Http::assertSent(fn (Request $r): bool => ! $r->hasHeader('Authorization'));

        config(['services.brapi.key' => 'secret']);
        app(BrapiClient::class)->tickers();
        Http::assertSent(fn (Request $r): bool => $r->header('Authorization') === ['Bearer secret']);
    }

    public function test_it_waits_for_retry_after_on_429_then_succeeds(): void
    {
        Http::fake([
            'brapi.test/*' => Http::sequence()
                ->push(['error' => true, 'code' => 'RATE_LIMIT'], 429, ['Retry-After' => '7'])
                ->push(['results' => [['symbol' => 'AAA3']], 'pagination' => ['hasNextPage' => false]]),
        ]);

        $results = app(BrapiClient::class)->tickers();

        $this->assertCount(1, $results);
        Sleep::assertSlept(fn ($duration): bool => (int) $duration->totalSeconds === 7, 1);
    }

    public function test_it_retries_server_errors_then_throws_when_exhausted(): void
    {
        Http::fake(['brapi.test/*' => Http::response(['error' => true, 'code' => 'UNAVAILABLE'], 503)]);

        try {
            app(BrapiClient::class)->tickers();
            $this->fail('Expected BrapiException.');
        } catch (BrapiException $e) {
            $this->assertSame(503, $e->status);
            $this->assertSame('UNAVAILABLE', $e->errorCode);
        }

        Http::assertSentCount(3);
        Sleep::assertSleptTimes(2);
    }

    public function test_it_does_not_retry_client_errors(): void
    {
        Http::fake(['brapi.test/*' => Http::response(['error' => true, 'code' => 'INVALID_TOKEN'], 401)]);

        $this->expectException(BrapiException::class);

        try {
            app(BrapiClient::class)->tickers();
        } finally {
            Http::assertSentCount(1);
            Sleep::assertNeverSlept();
        }
    }

    public function test_it_rejects_a_response_without_results(): void
    {
        Http::fake(['brapi.test/*' => Http::response(['unexpected' => true])]);

        $this->expectException(BrapiException::class);

        app(BrapiClient::class)->tickers();
    }

    public function test_it_aborts_when_pagination_never_ends(): void
    {
        Http::fake(['brapi.test/*' => Http::response(['results' => [], 'pagination' => ['hasNextPage' => true]])]);

        $this->expectException(BrapiException::class);

        app(BrapiClient::class)->tickers();
    }
}
