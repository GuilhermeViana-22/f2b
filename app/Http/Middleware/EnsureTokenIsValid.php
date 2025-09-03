<?php

namespace App\Http\Middleware;

use App\Exceptions\MissingTokenException;
use App\Exceptions\InvalidTokenException;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Laravel\Passport\Exceptions\MissingScopeException;
use Laravel\Passport\Exceptions\OAuthServerException;

class EnsureTokenIsValid
{
    public function handle(Request $request, Closure $next): Response
    {
        // A validação de autenticação agora é centralizada no middleware `auth:api`.
        // Este middleware foi simplificado para evitar conflitos e redundância.
        return $next($request);
}