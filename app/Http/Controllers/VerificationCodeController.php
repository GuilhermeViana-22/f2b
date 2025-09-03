<?php

namespace App\Http\Controllers;

use App\Http\Resources\Auth\UserResource;
use App\Http\Requests\CodeRequest;
use App\Models\Log;
use Illuminate\Support\Facades\DB;
use App\Models\VerificationCode;
use Illuminate\Http\Response;
use App\Models\User;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

class VerificationCodeController extends Controller
{

    public function verifyCode(CodeRequest $request)
    {
        // Buscar o código de verificação no banco de dados
        $verificationCode = VerificationCode::where('code', $request->get('code'))->first();

        if ($verificationCode) {

            DB::beginTransaction();

            try {
                // Encontrar o utilizador pelo ID
                $user = User::findOrFail($verificationCode->user_id);
                $user->update([
                    'active' => User::USUARIO_ATIVO,
                    'situacao_id' => User::SITUACAO_ATIVA,
                ]);

                try {
                    // Revoke any existing tokens
                    $user->tokens()->where('revoked', false)->update(['revoked' => true]);
                    $tokenResult = $user->createToken('Personal Access Token');
                    $token = $tokenResult->accessToken;
                    $this->logAccess($user->id, $request->ip(), $request->path(), true, $user->name);
                } catch (\Exception $e) {
                    DB::rollBack();
                    return response()->json(['error' => 'Erro ao gerar o token de acesso. ' . $e->getMessage()], ResponseAlias::HTTP_INTERNAL_SERVER_ERROR);
                }

                DB::commit();
                return response()->json([
                    'user' => new UserResource($user),
                    'token' => $token,
                ], 200);
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json(['error' => 'Erro ao encontrar o usuário. ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        } else {
            return response()->json(['error' => 'Código de verificação inválido.'], Response::HTTP_BAD_REQUEST);
        }
    }

    private function logAccess($userId, $ip, $rota, $autenticado = true, $name = null): void
    {
        try {
            \App\Models\Log::create([
                'user_id' => $userId,
                'ip' => $ip,
                'rota' => $rota,
                'autenticado' => $autenticado,
                'name' => $name,
                'created_at' => now()
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao registrar log de acesso: ' . $e->getMessage());
        }
    }
}
