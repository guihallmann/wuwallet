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

        $users = ($manager->isManager() ? $manager->analysts() : $manager->clients())
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'tax_id', 'active']);

        return Inertia::render('users/index', [
            'users' => $users,
            'managedRole' => $this->managedRole($manager)->value,
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
            'tax_id' => $request->validated('tax_id'),
            'email' => $request->validated('email'),
            'password' => Hash::make((string) $request->validated('password')),
            'active' => $request->boolean('active'),
            'email_verified_at' => now(),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('User created.')]);

        return to_route('users.index');
    }

    public function edit(User $user): Response
    {
        Gate::authorize('update', $user);

        return Inertia::render('users/edit', [
            'user' => $user->only(['id', 'name', 'email', 'tax_id', 'active']),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $user->fill([
            'name' => $request->validated('name'),
            'tax_id' => $request->validated('tax_id'),
            'email' => $request->validated('email'),
            'active' => $request->boolean('active'),
        ]);

        if (filled($request->validated('password'))) {
            $user->password = Hash::make((string) $request->validated('password'));
        }

        $user->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('User updated.')]);

        return to_route('users.index');
    }

    private function managedRole(User $manager): UserRole
    {
        return $manager->isManager() ? UserRole::ANALYST : UserRole::CLIENT;
    }
}
