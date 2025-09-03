<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\AuthenticationException;
use Laravel\Passport\Exceptions\OAuthServerException;
use Laravel\Passport\Exceptions\MissingScopeException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Throwable $exception
     * @return \Illuminate\Http\JsonResponse
     */
    public function render($request, Throwable $exception)
    {
        // Tratamento de exceções de validação
        if ($exception instanceof ValidationException) {
            return response()->json([
                'success' => false,
                'message' => 'The data provided is invalid',
                'errors' => $exception->validator->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Tratamento de exceções de autenticação
        if ($exception instanceof AuthenticationException) {
            return response()->json([
                'success' => false,
                'message' => 'Token de autenticação não fornecido ou inválido',
                'error_code' => 'AUTHENTICATION_FAILED',
                'documentation_url' => config('app.url') . '/api/documentation'
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Tratamento de exceções do Passport
        if ($exception instanceof OAuthServerException) {
            return response()->json([
                'success' => false,
                'message' => 'Erro de autenticação OAuth',
                'error_code' => 'OAUTH_ERROR',
                'documentation_url' => config('app.url') . '/api/documentation'
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Tratamento de exceções de permissões
        if ($exception instanceof MissingScopeException) {
            return response()->json([
                'success' => false,
                'message' => 'Token não possui as permissões necessárias',
                'error_code' => 'INSUFFICIENT_PERMISSIONS',
                'documentation_url' => config('app.url') . '/api/documentation'
            ], Response::HTTP_FORBIDDEN);
        }

        // Tratamento de exceções de modelo não encontrado
        if ($exception instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'Recurso não encontrado',
                'error_code' => 'RESOURCE_NOT_FOUND',
                'documentation_url' => config('app.url') . '/api/documentation'
            ], Response::HTTP_NOT_FOUND);
        }

        // Tratamento de exceções de query
        if ($exception instanceof \Illuminate\Database\QueryException) {
            return response()->json([
                'success' => false,
                'message' => 'Erro no banco de dados',
                'error_code' => 'DATABASE_ERROR',
                'documentation_url' => config('app.url') . '/api/documentation'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        // Para requisições API, sempre retornar JSON
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => config('app.debug') ? $exception->getMessage() : 'Erro interno do servidor',
                'error_code' => 'INTERNAL_SERVER_ERROR',
                'documentation_url' => config('app.url') . '/api/documentation'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return parent::render($request, $exception);
    }
}
