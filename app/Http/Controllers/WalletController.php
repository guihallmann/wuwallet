<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreWalletRequest;
use App\Http\Requests\UpdateWalletRequest;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
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
            'totalValue' => $wallets->sum(fn (Wallet $wallet) => $wallet->totalValue()),
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
            'totalValue' => $wallets->sum(fn (Wallet $wallet) => $wallet->totalValue()),
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', Wallet::class);

        return Inertia::render('wallets/create');
    }

    public function store(StoreWalletRequest $request): RedirectResponse
    {
        $request->user()->wallets()->create([
            'name' => $request->validated('name'),
            'objective_text' => $request->validated('objective_text'),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Carteira criada')]);

        return to_route('wallets.index');
    }

    public function edit(Wallet $wallet): Response
    {
        Gate::authorize('update', $wallet);

        return Inertia::render('wallets/edit', [
            'wallet' => $wallet->only(['id', 'name', 'objective_text']),
        ]);
    }

    public function update(UpdateWalletRequest $request, Wallet $wallet): RedirectResponse
    {
        $wallet->update([
            'name' => $request->validated('name'),
            'objective_text' => $request->validated('objective_text'),
        ]);

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
     * @return list<array{id: string, name: string, objective_text: string|null, total_value: float}>
     */
    private function mapWallets(Collection $wallets): array
    {
        return array_values($wallets
            ->map(fn (Wallet $wallet) => [
                'id' => $wallet->id,
                'name' => $wallet->name,
                'objective_text' => $wallet->objective_text,
                'total_value' => $wallet->totalValue(),
            ])
            ->all());
    }
}
