<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class InvalidTokenException extends Exception
{
    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Token de autenticação inválido ou expirado',
            'error_code' => 'INVALID_AUTH_TOKEN',
            'documentation_url' => config('app.url') . '/api/documentation'
        ], Response::HTTP_UNAUTHORIZED);
    }
} 