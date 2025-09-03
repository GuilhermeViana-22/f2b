<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
class ThrottleRequestsCustom
{
    /**
     * Handle an incoming request.
     * @param Request $request
     * @param Closure(Request): (Response|RedirectResponse) $next
     * @return Response|RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        return $next($request);
    }

    protected function buildException($key, $maxAttempts)
    {
        // Aqui você define a mensagem customizada
        throw new ThrottleRequestsException(
            response()->json([
                'message' => 'inumeras tentativas',
            ], 429)
        );
    }
}
