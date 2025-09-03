<?php

namespace App\Http\Controllers\Auth;



use App\Models\Log;
use App\Models\User;
use App\Mail\SendMail;
use App\Mail\ResetPassword;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Traits\RegistraAcesso;
use Illuminate\Support\Carbon;
use App\Models\VerificationCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Http\Requests\Auth\MeRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\ResetRequest;
use App\Http\Resources\Auth\UserResource;
use App\Http\Requests\Auth\MailVerifyRequest;
use App\Http\Requests\Auth\DeleteAccountRequest;
use App\Http\Requests\Auth\UserRegisterValidationRequest;

/**
 * @OA\Info(
 *     title="Sistema de Gerenciamento - BOB FLOW",
 *     version="1.0.0",
 *     description="API para gerenciamento BOB FLOW",
 *     @OA\Contact(email="suporte@sistema.com")
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT"
 * )
 */
class AuthController extends Controller
{
    use RegistraAcesso;

    /**
     * @OA\Post(
     *     path="/api/auth/register",
     *     summary="Registrar um novo usuário",
     *     tags={"Autenticação"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"name", "email", "password", "password_confirmation", "telefone_celular"},
     *                 @OA\Property(property="name", type="string", example="João da Silva"),
     *                 @OA\Property(property="email", type="string", format="email", example="joao@empresa.com"),
     *                 @OA\Property(property="password", type="string", format="password", example="Senha123@"),
     *                 @OA\Property(property="password_confirmation", type="string", format="password", example="Senha123@"),
     *                 @OA\Property(property="telefone_celular", type="string", example="(11) 99999-9999"),
     *                 @OA\Property(property="cpf", type="string", example="123.456.789-09", nullable=true),
     *                 @OA\Property(property="data_nascimento", type="string", format="date", example="1990-01-01", nullable=true),
     *                 @OA\Property(
     *                     property="genero",
     *                     type="string",
     *                     enum={"masculino", "feminino", "outro", "prefiro não informar"},
     *                     example="masculino",
     *                     nullable=true
     *                 ),
     *                 @OA\Property(property="position_id", type="integer", example=1, nullable=true),
     *                 @OA\Property(property="company_id", type="integer", example=1, nullable=true),
     *                 @OA\Property(property="status_id", type="integer", example=1, nullable=true),
     *                 @OA\Property(property="situacao_id", type="integer", example=1, nullable=true),
     *                 @OA\Property(
     *                     property="foto_perfil",
     *                     type="string",
     *                     format="binary",
     *                     description="Imagem de perfil (formatos: jpeg,png,jpg,gif, máximo 2MB)",
     *                     nullable=true
     *                 ),
     *                 @OA\Property(property="ativo", type="boolean", example=true, nullable=true)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Usuário registrado com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Usuário registrado com sucesso"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="user", ref="#/components/schemas/User"),
     *                 @OA\Property(property="access_token", type="string", example="eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9..."),
     *                 @OA\Property(property="token_type", type="string", example="Bearer")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erro de validação",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Os dados fornecidos são inválidos."),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 example={
     *                     "name": {"O campo nome completo é obrigatório."},
     *                     "email": {"Este email já está cadastrado"}
     *                 }
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erro interno no servidor",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Erro ao registrar usuário"),
     *             @OA\Property(property="error", type="string", example="Mensagem detalhada do erro")
     *         )
     *     )
     * )
     */
    public function register(UserRegisterValidationRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            if (User::where('email', $request->email)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'O email informado já está em uso',
                    'errors' => [
                        'email' => ['Este email já está cadastrado']
                    ]
                ], 422);
            }

            $data = $request->validated();
            $data['password'] = bcrypt($data['password']);
            unset($data['foto_perfil']);

            $user = User::create($data);
            $token = $user->createToken('auth_token')->accessToken;

            // Substitua esta linha:
            // $this->logAccess($user->id, $request->ip(), $request->path(), true, $user->name);
            // Por:
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Usuário registrado com sucesso',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'telefone_celular' => $user->telefone_celular,
                        'created_at' => $user->created_at,
                        'updated_at' => $user->updated_at
                    ],
                    'access_token' => $token,
                    'token_type' => 'Bearer'
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erro ao registrar usuário',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/auth/login",
     *     summary="Login de um usuário",
     *     tags={"Autenticação"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email", "password"},
     *             @OA\Property(property="email", type="string", format="email", example="usuario@empresa.com"),
     *             @OA\Property(property="password", type="string", format="password", example="Senha123@")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Login bem-sucedido",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Login realizado com sucesso"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="user", ref="#/components/schemas/User"),
     *                 @OA\Property(property="token", type="string", example="eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9..."),
     *                 @OA\Property(property="token_type", type="string", example="Bearer"),
     *                 @OA\Property(property="expires_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Credenciais inválidas",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Credenciais inválidas"),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 example={
     *                     "email": {"Credenciais fornecidas são inválidas."}
     *                 }
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erro interno",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Erro ao realizar o login")
     *         )
     *     )
     * )
     */
    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $credentials = $request->only('email', 'password');

            if (!auth()->attempt($credentials)) {
                // Registrar tentativa fracassada diretamente na tabela failed_jobs
                DB::beginTransaction();

                DB::table('failed_jobs')->insert([
                    'uuid' => (string) Str::uuid(),
                    'connection' => 'database',
                    'queue' => 'default',
                    'payload' => json_encode([
                        'email' => $request->email,
                        'ip' => $request->ip(),
                        'attempted_at' => now()->toDateTimeString()
                    ]),
                    'exception' => json_encode([
                        'message' => 'Credenciais inválidas',
                        'type' => 'AuthenticationException',
                        'status' => 401
                    ]),
                    'failed_at' => now()
                ]);

                DB::commit();

                return response()->json([
                    'success' => false,
                    'message' => 'Credenciais inválidas'
                ], 401);
            }

            $user = auth()->user();
            $tokenResult = $user->createToken('Personal Access Token');
            $token = $tokenResult->accessToken;

            // Registrar acesso com sucesso
            $this->logAccess($user->id, $request->ip(), $request->path(), true, $user->name);

            return response()->json([
                'success' => true,
                'message' => 'Login realizado com sucesso',
                'data' => [
                    'user' => $user->only(['id', 'name', 'email', 'telefone_celular', 'foto_perfil']),
                    'token' => $token,
                    'token_type' => 'Bearer',
                    'expires_at' => $tokenResult->token->expires_at
                ]
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erro ao realizar login',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
    /**
     * @OA\Get(
     *     path="/api/auth/me",
     *     summary="Retorna dados do usuário autenticado",
     *     tags={"Autenticação"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="query",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object", ref="#/components/schemas/User")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Usuário não encontrado",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Usuário não encontrado")
     *         )
     *     )
     * )
     */
    public function me(MeRequest $request): UserResource
    {
        try {
            $user = Auth::guard('api')->user()->load([
                'company',
                'position.permissions',
                'logs',
            ]);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuário autenticado não encontrado'
                ], 401);
            }

            return (new UserResource($user))
                ->additional([
                    'success' => true
                ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar dados do usuário',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/auth/logout",
     *     summary="Encerra a sessão do usuário",
     *     tags={"Autenticação"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Logout bem-sucedido",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Logout realizado com sucesso")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Não autorizado",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Token não fornecido ou inválido")
     *         )
     *     )
     * )
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nenhum usuário autenticado'
                ], 401);
            }

            $user->token()->revoke();

            // Substitua esta linha:
            // $this->logAccess($user->id, $request->ip(), $request->path(), true, $user->name);
            // Por:
            $this->logAccess($user->id, $request->ip(), $request->path(), true, $user->name);


            return response()->json([
                'success' => true,
                'message' => 'Logout realizado com sucesso'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao realizar logout',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/auth/mail-verify",
     *     summary="Envia código de verificação por email",
     *     tags={"Autenticação"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email"},
     *             @OA\Property(property="email", type="string", format="email", example="usuario@empresa.com")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Código enviado com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Verificação do código enviada. Por gentileza cheque seu e-mail.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Email não encontrado",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Não foi localizado este e-mail nos nossos registros")
     *         )
     *     )
     * )
     */
    public function mailVerify(MailVerifyRequest $request): JsonResponse
    {
        $user = User::where('email', 'like', '%' . $request->get('email') . '%')->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Não foi localizado este e-mail nos nossos registros, por gentileza verifique o email digitado e tente novamente.'
            ], 404);
        }

        try {
            DB::beginTransaction();

            $code = Str::random(6);

            VerificationCode::create([
                'user_id' => $user->id,
                'email' => $user->email,
                'code' => $code,
            ]);

            $this->sendResetPassword($user->email, $code, $user->name);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Verificação do código enviada. Por gentileza cheque seu e-mail.'
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erro ao enviar código de verificação. Por favor, tente novamente.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/auth/reset-password",
     *     summary="Redefine a senha do usuário",
     *     tags={"Autenticação"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"password"},
     *             @OA\Property(property="password", type="string", format="password", example="NovaSenha123")
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="user_id",
     *         in="header",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Senha redefinida com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Senha alterada com sucesso")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Código inválido ou expirado",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Código de verificação inválido ou expirado")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Header ausente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Header não foi fornecido corretamente")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Usuário não encontrado",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Usuário não encontrado")
     *         )
     *     )
     * )
     */
    public function reset(ResetRequest $request): JsonResponse
    {
        $userIdHeader = $request->header('user_id');
        if (!$userIdHeader) {
            return response()->json([
                'success' => false,
                'message' => 'Header não foi fornecido corretamente.'
            ], 401);
        }

        $dados = $request->validated();

        $user = User::find($userIdHeader);
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Usuário não encontrado.'
            ], 404);
        }

        $verification = VerificationCode::where('user_id', $userIdHeader)->first();

        if (!$verification) {
            return response()->json([
                'success' => false,
                'message' => 'Código de verificação inválido ou expirado.'
            ], 400);
        }

        try {
            $user->password = Hash::make($dados['password']);
            $user->save();

            $verification->delete();

            return response()->json([
                'success' => true,
                'message' => 'Senha alterada com sucesso.'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao alterar senha. Por favor, tente novamente.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/auth/validate-token",
     *     summary="Valida um token de acesso",
     *     tags={"Autenticação"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"user_id", "token"},
     *             @OA\Property(property="user_id", type="integer", example=1),
     *             @OA\Property(property="token", type="string", example="token_jwt_aqui")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Token válido",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="authorized", type="boolean", example=true),
     *                 @OA\Property(property="message", type="string", example="Token válido")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Token inválido",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Token inválido")
     *         )
     *     )
     * )
     */
    public function validateToken(Request $request): JsonResponse
    {
        try {
            $userId = $request->get('user_id');
            $token = $request->get('token');

            $tokenRecord = DB::table('personal_access_tokens')
                ->where('tokenable_id', $userId)
                ->where('token', $token)
                ->first();

            if ($tokenRecord) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'authorized' => true,
                        'message' => 'Token válido.'
                    ]
                ], 200);
            }

            return response()->json([
                'success' => false,
                'message' => 'Token inválido.'
            ], 401);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao validar token',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/auth/delete-account",
     *     summary="Deleta a conta do usuário",
     *     tags={"Autenticação"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Conta deletada com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Conta deletada com sucesso")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Não autorizado",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Token não fornecido ou inválido")
     *         )
     *     )
     * )
     */
    public function delete(DeleteAccountRequest $request): JsonResponse
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nenhum usuário autenticado'
                ], 401);
            }

            $user->tokens()->delete();
            $user->delete();

            return response()->json([
                'success' => true,
                'message' => 'Conta deletada com sucesso'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao deletar conta',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    private function sendVerificationEmail($email, $code, $name): void
    {
        Mail::to($email)->send(new SendMail($code, $name));
    }

    private function sendResetPassword($email, $code, $name): void
    {
        Mail::to($email)->send(new ResetPassword($code, $name));
    }

    private function logAccess($userId, $ip, $rota, $autenticado = true, $name = null)
    {
        try {
            // Verificar se já existe um log idêntico recentemente
            $existingLog = Log::where('user_id', $userId)
                ->where('ip', $ip)
                ->where('rota', $rota)
                ->where('created_at', '>', now()->subMinutes(5))
                ->first();

            if (!$existingLog) {
                Log::create([
                    'user_id' => $userId,
                    'ip' => $ip,
                    'rota' => $rota,
                    'autenticado' => $autenticado,
                    'name' => $name,
                    'created_at' => Carbon::now()
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Não foi possivel gerar o log de alteração de dados na api' . $e->getMessage()
            ], 500);
        }
    }
}
