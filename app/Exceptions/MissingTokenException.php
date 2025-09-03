<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class MissingTokenException extends Exception
{
    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Token de autenticação não fornecido',
            'error_code' => 'MISSING_AUTH_TOKEN',
            'documentation_url' => config('app.api_docs_url').'/authentication'
        ], Response::HTTP_UNAUTHORIZED);
    }
}