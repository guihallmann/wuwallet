<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $manager = $request->user();

        Gate::authorize('viewAny', User::class);

        $managedRole = $this->managedRole($manager);
        $search = trim((string) $request->string('search'));

        $summary = null;

        if ($managedRole === UserRole::CLIENT) {
            $clients = $manager->clients()->with('wallets.assets.asset')->get();

            $summary = [
                'totalPatrimony' => $clients->sum(
                    fn (User $client) => $client->wallets->sum(fn ($wallet) => $wallet->totalValue())
                ),
                'totalClients' => $clients->count(),
                'activeClients' => $clients->where('active', true)->count(),
            ];
        }

        $users = ($manager->isManager() ? $manager->analysts() : $manager->clients())
            ->when($search !== '', fn ($query) => $query->where('name', 'like', "%{$search}%"))
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'active']);

        return Inertia::render('users/index', [
            'users' => $users,
            'managedRole' => $managedRole->value,
            'summary' => $summary,
            'filters' => ['search' => $search],
        ]);
    }

    public function create(Request $request): Response
    {
        Gate::authorize('viewCreateForm', User::class);

        return Inertia::render('users/create', [
            'managedRole' => $this->managedRole($request->user())->value,
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $manager = $request->user();

        User::query()->create([
            'manager_id' => $manager->getKey(),
            'role' => $this->managedRole($manager),
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'recovery_email' => $request->validated('recovery_email'),
            'password' => Hash::make((string) $request->validated('password')),
            'active' => $request->boolean('active'),
            'email_verified_at' => now(),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Usuário criado')]);

        return to_route('users.index');
    }

    public function edit(User $user): Response
    {
        Gate::authorize('update', $user);

        return Inertia::render('users/edit', [
            'user' => $user->only(['id', 'name', 'email', 'recovery_email', 'active']),
            'managedRole' => $user->role->value,
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $user->fill([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'recovery_email' => $request->validated('recovery_email'),
            'active' => $request->boolean('active'),
        ]);

        $user->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Usuário atualizado')]);

        return to_route('users.index');
    }

    private function managedRole(User $manager): UserRole
    {
        return $manager->isManager() ? UserRole::ANALYST : UserRole::CLIENT;
    }
}
