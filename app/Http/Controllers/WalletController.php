<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreWalletRequest;
use App\Http\Requests\UpdateWalletRequest;
use App\Models\Asset;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletAsset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class WalletController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Wallet::class);

        $wallets = $request->user()->wallets()->with('assets.asset')->orderBy('name')->get();

        return Inertia::render('wallets/index', [
            'mode' => 'manage',
            'wallets' => $this->mapWallets($wallets),
            'totalValue' => $wallets->sum(fn(Wallet $wallet) => $wallet->totalValue()),
        ]);
    }

    public function clientWallets(User $client): Response
    {
        Gate::authorize('view', $client);

        abort_unless($client->isClient(), 404);

        $wallets = $client->wallets()->with('assets.asset')->orderBy('name')->get();

        return Inertia::render('wallets/index', [
            'mode' => 'view',
            'client' => $client->only(['id', 'name']),
            'wallets' => $this->mapWallets($wallets),
            'totalValue' => $wallets->sum(fn(Wallet $wallet) => $wallet->totalValue()),
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', Wallet::class);

        return Inertia::render('wallets/create', [
            'sectors' => $this->assetSectors(),
        ]);
    }

    public function searchAssets(Request $request): JsonResponse
    {
        Gate::authorize('create', Wallet::class);

        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:50'],
            'sector' => ['nullable', 'string', 'max:100'],
        ]);

        $assets = Asset::query()
            ->select(['id', 'ticker', 'name', 'sector', 'current_price'])
            ->when($filters['q'] ?? null, function ($query, string $term): void {
                $query->where(function ($query) use ($term): void {
                    $query->where('ticker', 'like', "%{$term}%")
                        ->orWhere('name', 'like', "%{$term}%");
                });
            })
            ->when($filters['sector'] ?? null, fn($query, string $sector) => $query->where('sector', $sector))
            ->orderBy('ticker')
            ->limit(20)
            ->get();

        return response()->json($assets);
    }

    public function store(StoreWalletRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request): void {
            $wallet = $request->user()->wallets()->create([
                'name' => $request->validated('name'),
                'objective_text' => $request->validated('objective_text'),
            ]);

            $wallet->assets()->createMany(array_map(
                fn(array $asset): array => [
                    'asset_id' => $asset['asset_id'],
                    'target_percentage' => $asset['target_percentage'],
                ],
                $request->validated('assets'),
            ));
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Carteira criada')]);

        return to_route('wallets.index');
    }

    public function show(Wallet $wallet): Response
    {
        Gate::authorize('view', $wallet);

        $wallet->load('assets.asset');
        $totalValue = $wallet->totalValue();

        return Inertia::render('wallets/show', [
            'wallet' => [
                ...$wallet->only(['id', 'name', 'objective_text']),
                'total_value' => $totalValue,
                'assets' => $wallet->assets
                    ->sortByDesc(fn(WalletAsset $walletAsset): float => (float) $walletAsset->target_percentage)
                    ->map(function (WalletAsset $walletAsset) use ($totalValue): array {
                        $value = $walletAsset->current_quantity * (float) $walletAsset->asset->current_price;

                        return [
                            'ticker' => $walletAsset->asset->ticker,
                            'name' => $walletAsset->asset->name,
                            'sector' => $walletAsset->asset->sector,
                            'current_price' => (float) $walletAsset->asset->current_price,
                            'current_quantity' => $walletAsset->current_quantity,
                            'value' => $value,
                            'target_percentage' => (float) $walletAsset->target_percentage,
                            'current_percentage' => $totalValue > 0 ? round($value / $totalValue * 100, 2) : 0,
                        ];
                    })
                    ->values()
                    ->all(),
            ],
        ]);
    }

    public function edit(Wallet $wallet): Response
    {
        Gate::authorize('update', $wallet);

        return Inertia::render('wallets/edit', [
            'wallet' => [
                ...$wallet->only(['id', 'name', 'objective_text']),
                'assets' => $wallet->assets()->with('asset')->get()->map(fn($walletAsset): array => [
                    'asset_id' => $walletAsset->asset_id,
                    'ticker' => $walletAsset->asset->ticker,
                    'name' => $walletAsset->asset->name,
                    'target_percentage' => $walletAsset->target_percentage,
                    'current_quantity' => $walletAsset->current_quantity,
                ])->values(),
            ],
            'sectors' => $this->assetSectors(),
        ]);
    }

    public function update(UpdateWalletRequest $request, Wallet $wallet): RedirectResponse
    {
        DB::transaction(function () use ($request, $wallet): void {
            $wallet = $request->user()
                ->wallets()
                ->whereKey($wallet->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $wallet->update([
                'name' => $request->validated('name'),
                'objective_text' => $request->validated('objective_text'),
            ]);

            $assets = $request->validated('assets');
            $assetIds = array_column($assets, 'asset_id');

            $assetsToRemove = $wallet->assets()
                ->whereNotIn('asset_id', $assetIds)
                ->where('current_quantity', '>', 0)
                ->exists();

            if ($assetsToRemove) {
                throw ValidationException::withMessages([
                    'assets' => 'Não é possível remover um ativo que possui quantidade na carteira.',
                ]);
            }

            $wallet->assets()->whereNotIn('asset_id', $assetIds)->delete();

            foreach ($assets as $asset) {
                $wallet->assets()->updateOrCreate(
                    ['asset_id' => $asset['asset_id']],
                    ['target_percentage' => $asset['target_percentage']],
                );
            }
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Carteira atualizada')]);

        return to_route('wallets.index');
    }

    public function destroy(Wallet $wallet): RedirectResponse
    {
        Gate::authorize('delete', $wallet);

        $wallet->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Carteira excluída')]);

        return to_route('wallets.index');
    }

    /**
     * @param  Collection<int, Wallet>  $wallets
     * @return list<array{id: string, name: string, objective_text: string|null, total_value: float, assets_count: int, allocation: list<array{ticker: string, name: string, target_percentage: string, current_quantity: int}>}>
     */
    private function mapWallets(Collection $wallets): array
    {
        return array_values($wallets
            ->map(fn(Wallet $wallet) => [
                'id' => $wallet->id,
                'name' => $wallet->name,
                'objective_text' => $wallet->objective_text,
                'total_value' => $wallet->totalValue(),
                'assets_count' => $wallet->assets->count(),
                'allocation' => $wallet->assets
                    ->sortByDesc(fn($walletAsset): float => (float) $walletAsset->target_percentage)
                    ->map(fn($walletAsset): array => [
                        'ticker' => $walletAsset->asset->ticker,
                        'name' => $walletAsset->asset->name,
                        'target_percentage' => $walletAsset->target_percentage,
                        'current_quantity' => $walletAsset->current_quantity,
                    ])
                    ->values()
                    ->all(),
            ])
            ->all());
    }

    /** @return list<string> */
    private function assetSectors(): array
    {
        return Cache::remember(
            'asset_sectors_v2',
            now()->addMinutes(5),
            fn(): array => Asset::query()->select('sector')->distinct()->orderBy('sector')->pluck('sector')->all()
        );
    }
}
