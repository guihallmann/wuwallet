<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Requests\ClientInvitationRegistrationRequest;
use App\Http\Requests\ClientInvitationRequest;
use App\Mail\ClientInvitationMail;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;
use Inertia\Inertia;
use Inertia\Response;

class ClientInvitationController extends Controller
{
    public function create(): Response
    {
        Gate::authorize('sendInvitations', User::class);

        return Inertia::render('analyst/invites/create');
    }

    public function store(ClientInvitationRequest $request): RedirectResponse
    {
        $analyst = $request->user();

        $email = strtolower(trim((string) $request->validated('email')));

        $inviteUrl = URL::temporarySignedRoute(
            'register.invite',
            now()->addHours(48),
            [
                'analyst_id' => $analyst->getKey(),
                'email' => $email,
            ],
        );

        Mail::to($email)->send(new ClientInvitationMail($email, $inviteUrl));

        return back()->with('status', __('Client invitation sent successfully.'));
    }

    public function showRegistrationForm(Request $request, string $analystId): View
    {
        abort_unless($request->hasValidSignature(), 403);

        $analyst = User::query()->findOrFail($analystId);

        if (! $analyst->isAnalyst()) {
            abort(403);
        }

        $email = (string) $request->query('email', '');

        if ($email === '') {
            abort(403);
        }

        return view('auth.invite-register', [
            'analyst' => $analyst,
            'email' => $email,
        ]);
    }

    public function register(ClientInvitationRegistrationRequest $request, string $analystId): RedirectResponse
    {
        abort_unless($request->hasValidSignature(), 403);

        $analyst = User::query()->findOrFail($analystId);

        if (! $analyst->isAnalyst()) {
            abort(403);
        }

        $email = (string) $request->query('email', '');

        if ($email === '') {
            abort(403);
        }

        $user = User::query()->create([
            'manager_id' => $analyst->getKey(),
            'role' => UserRole::CLIENT,
            'name' => $request->validated('name'),
            'cpf' => $request->validated('cpf'),
            'email' => $email,
            'password' => Hash::make($request->validated('password')),
            'email_verified_at' => now(),
        ]);

        Auth::login($user);

        return redirect()->route('dashboard')->with('status', __('Welcome aboard!'));
    }
}
