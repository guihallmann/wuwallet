<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Asset;
use App\Models\Wallet;
use App\Models\WalletAsset;

/**
 * @phpstan-type SuggestionItem array{
 *     asset_id: int,
 *     ticker: string,
 *     name: string,
 *     status: 'buy'|'above_target'|'lot_too_expensive'|'lower_priority'|'unavailable',
 *     current_price: float,
 *     current_quantity: int,
 *     lots: int,
 *     quantity: int,
 *     cost: float,
 *     lot_cost: float,
 *     current_percentage: float,
 *     after_percentage: float,
 *     target_percentage: float,
 * }
 * @phpstan-type Suggestion array{
 *     summary: array{
 *         amount: float,
 *         spent: float,
 *         leftover: float,
 *         total_before: float,
 *         total_after: float,
 *         lot_size: int,
 *         cheapest_lot: array{ticker: string, cost: float}|null,
 *         prices_updated_at: string|null,
 *     },
 *     items: list<SuggestionItem>,
 * }
 * @phpstan-type Position array{
 *     walletAsset: WalletAsset,
 *     tradable: bool,
 *     lot: int,
 *     value: int,
 *     deficit: float,
 * }
 */
class BuySuggestionService
{
    /**
     * Least-squares water-filling rounded down to whole lots, then the leftover is spent greedily lot by lot.
     *
     * @return Suggestion
     */
    public function suggest(Wallet $wallet, int $amountCents): array
    {
        $wallet->loadMissing('assets.asset');

        $holdings = [];

        foreach ($wallet->assets->sortBy(fn(WalletAsset $walletAsset): string => $walletAsset->asset->ticker) as $walletAsset) {
            $priceCents = (int) round((float) $walletAsset->asset->current_price * 100);

            $holdings[] = [
                'walletAsset' => $walletAsset,
                'tradable' => $walletAsset->asset->isTradable(),
                'lot' => $priceCents * Asset::LOT_SIZE,
                'value' => $walletAsset->current_quantity * $priceCents,
            ];
        }

        $totalBefore = array_sum(array_column($holdings, 'value'));
        $targetTotal = $totalBefore + $amountCents;

        $positions = array_map(function (array $holding) use ($targetTotal): array {
            $targetBasisPoints = (int) round((float) $holding['walletAsset']->target_percentage * 100);

            return $holding + ['deficit' => $targetBasisPoints * $targetTotal / 10000.0 - $holding['value']];
        }, $holdings);

        $shares = $this->waterFill(
            array_map(
                fn(array $position): float => $position['deficit'],
                array_filter($positions, fn(array $position): bool => $position['tradable']),
            ),
            $amountCents,
        );
        $lots = array_fill(0, count($positions), 0);
        $remaining = $amountCents;

        foreach ($shares as $i => $share) {
            // The epsilon keeps float noise from rounding a share up into a lot the budget can't cover.
            $lots[$i] = max(0, (int) floor($share / $positions[$i]['lot'] - 1e-9));
            $remaining -= $lots[$i] * $positions[$i]['lot'];
        }

        while (($best = $this->bestNextLot($positions, $lots, $remaining)) !== null) {
            $lots[$best]++;
            $remaining -= $positions[$best]['lot'];
        }

        return $this->present($positions, $lots, $amountCents, $remaining, $totalBefore);
    }

    /**
     * Continuous minimizer of Σ (d_i - x_i)² with Σ x_i = budget: x_i = max(0, d_i - λ).
     *
     * @param  array<int, float>  $deficits
     * @return array<int, float>
     */
    private function waterFill(array $deficits, int $budget): array
    {
        if ($deficits === []) {
            return [];
        }

        $sorted = array_values($deficits);
        rsort($sorted);

        $sum = 0.0;
        $lambda = 0.0;

        foreach ($sorted as $k => $deficit) {
            $sum += $deficit;
            $lambda = ($sum - $budget) / ($k + 1);

            if (($sorted[$k + 1] ?? -INF) <= $lambda) {
                break;
            }
        }

        return array_map(fn(float $deficit): float => max(0.0, $deficit - $lambda), $deficits);
    }

    /**
     * @param  list<Position>  $positions
     * @param  array<int, int>  $lots
     */
    private function bestNextLot(array $positions, array $lots, int $remaining): ?int
    {
        $best = null;
        $bestGain = -INF;

        foreach ($positions as $i => $position) {
            if (! $position['tradable'] || $position['lot'] > $remaining) {
                continue;
            }

            $deficit = $position['deficit'] - ($lots[$i] ?? 0) * $position['lot'];
            $gain = $position['lot'] * (2 * $deficit - $position['lot']);

            if ($gain > $bestGain) {
                $best = $i;
                $bestGain = $gain;
            }
        }

        return $best;
    }

    /**
     * @param  list<Position>  $positions
     * @param  array<int, int>  $lots
     * @return Suggestion
     */
    private function present(array $positions, array $lots, int $amountCents, int $remaining, int $totalBefore): array
    {
        $spent = $amountCents - $remaining;
        $totalAfter = $totalBefore + $spent;

        $items = array_map(function (array $position, int $lotCount) use ($amountCents, $totalBefore, $totalAfter): array {
            $walletAsset = $position['walletAsset'];
            $asset = $walletAsset->asset;
            $cost = $lotCount * $position['lot'];
            $afterPercentage = $totalAfter > 0 ? ($position['value'] + $cost) / $totalAfter * 100 : 0.0;
            $targetPercentage = (float) $walletAsset->target_percentage;

            $status = match (true) {
                ! $position['tradable'] => 'unavailable',
                $lotCount > 0 => 'buy',
                $afterPercentage >= $targetPercentage => 'above_target',
                $position['lot'] > $amountCents => 'lot_too_expensive',
                default => 'lower_priority',
            };

            return [
                'asset_id' => (int) $asset->id,
                'ticker' => $asset->ticker,
                'name' => $asset->name,
                'status' => $status,
                'current_price' => (float) $asset->current_price,
                'current_quantity' => $walletAsset->current_quantity,
                'lots' => $lotCount,
                'quantity' => $lotCount * Asset::LOT_SIZE,
                'cost' => $cost / 100.0,
                'lot_cost' => $position['lot'] / 100.0,
                'current_percentage' => $totalBefore > 0 ? round($position['value'] / $totalBefore * 100, 2) : 0.0,
                'after_percentage' => round($afterPercentage, 2),
                'target_percentage' => $targetPercentage,
            ];
        }, $positions, $lots);

        usort($items, fn(array $a, array $b): int => [$b['cost'], $b['target_percentage'], $a['ticker']]
            <=> [$a['cost'], $a['target_percentage'], $b['ticker']]);

        $tradable = array_values(array_filter($positions, fn(array $position): bool => $position['tradable']));
        usort($tradable, fn(array $a, array $b): int => $a['lot'] <=> $b['lot']);
        $cheapest = $tradable[0] ?? null;

        $priceDates = array_filter(array_map(
            fn(array $position) => $position['walletAsset']->asset->price_updated_at,
            $tradable,
        ));

        return [
            'summary' => [
                'amount' => $amountCents / 100.0,
                'spent' => $spent / 100.0,
                'leftover' => $remaining / 100.0,
                'total_before' => $totalBefore / 100.0,
                'total_after' => $totalAfter / 100.0,
                'lot_size' => Asset::LOT_SIZE,
                'cheapest_lot' => $cheapest === null ? null : [
                    'ticker' => $cheapest['walletAsset']->asset->ticker,
                    'cost' => $cheapest['lot'] / 100.0,
                ],
                'prices_updated_at' => $priceDates === [] ? null : min($priceDates)->toIso8601String(),
            ],
            'items' => $items,
        ];
    }
}
