<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreClientRegistrationRequest;
use App\Services\ClientRegistrationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ClientRegistrationController extends Controller
{
    public function show(Request $request, string $analystId): Response
    {
        abort_unless($request->hasValidSignature(), 403);

        $email = (string) $request->query('email', '');

        abort_if($email === '', 403);

        return Inertia::render('auth/client-register', [
            'analystId' => $analystId,
            'email' => $email,
            'query' => (string) $request->getQueryString(),
        ]);
    }

    public function store(StoreClientRegistrationRequest $request, string $analystId, ClientRegistrationService $service): RedirectResponse
    {
        abort_unless($request->hasValidSignature(), 403);

        $email = (string) $request->query('email', '');

        abort_if($email === '', 403);

        $service->registerClient([
            'nome' => (string) $request->validated('nome'),
            'cpf' => (string) $request->validated('cpf'),
            'password' => (string) $request->validated('password'),
        ], $analystId, $email);

        return to_route('login')->with('status', 'Cadastro realizado com sucesso. Faça seu login.');
    }
}
