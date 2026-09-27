<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureManagesUsers
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        abort_if($user === null || $user->isClient(), 401);

        return $next($request);
    }
}
