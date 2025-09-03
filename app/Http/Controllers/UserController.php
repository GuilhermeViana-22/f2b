<?php

namespace App\Http\Controllers;


use Carbon\Carbon;
use App\Models\User;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\UploadPhotoRequest;
use App\Http\Resources\Auth\UserResource;
use App\Http\Requests\Users\UsersIndexRequest;
use App\Http\Requests\Users\UserUpdateRequest;
use App\Http\Requests\Users\UserStoreRequest;
use App\Http\Requests\Users\UserRoleRequest;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;
class UserController extends Controller
{
    /**
     * Listar todos os utilizadores (com paginação)
     * @queryParam page integer Página atual. Example: 1
     * @queryParam per_page integer Itens por página. Example: 10
     * @queryParam name 'string' Filtro por nome. Example: João
     * @queryParam email string Filtro por email. Example: joao@example.com
     * @queryParam ativo boolean Filtro por status. Example: true
     * @queryParam unidade_id integer Filtro por unidade. Example: 1
     * @queryParam position_id integer Filtro por position. Example: 1
     * @queryParam sort string Campo para ordenação (name, email, created_at). Example: name
     * @queryParam order 'string' Direção da ordenação (asc, desc). Example: asc
     */
    public function index(UsersIndexRequest $request)
    {
        try {
            $query = User::query()->with([   'company',
                'position',  // Adicione este relacionamento
                'position.permissions',  // Carrega as permissões relacionadas à posição
                'logs',]);
            $query = $this->applyFilters($query, $request);

            // Ordenação padrão
            $sortField = $request->input('sort', 'name');
            $sortOrder = $request->input('order', 'asc');
            $query->orderBy($sortField, $sortOrder);

            $perPage = $request->input('per_page', 10);
            $users = $query->paginate($perPage);

            return UserResource::collection($users)
                ->additional([
                    'success' => true,
                    'message' => 'Usuários recuperados com sucesso'
                ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Falha ao recuperar usuários',
                'error' => $e->getMessage()
            ], ResponseAlias::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Criar novo usuário
     * @bodyParam name string required Nome do usuário. Example: João Silva
     * @bodyParam email string required Email do usuário. Example: joao@example.com
     * @bodyParam password string required Senha. Example: secret123
     * @bodyParam password_confirmation string required Confirmação da senha. Example: secret123
     * @bodyParam company_id integer ID da unidade. Example: 1
     * @bodyParam position_id integer ID do position. Example: 1
     */

    public function store(UserStoreRequest $request)
    {
        DB::beginTransaction();

        try {
            // Tratamento da data de nascimento
            $dataNascimento = null;
            if ($request->filled('data_nascimento')) {
                try {
                    $dataNascimento = Carbon::createFromFormat('Y-m-d', $request->data_nascimento);
                } catch (\Exception $e) {
                    try {
                        $dataNascimento = Carbon::createFromFormat('d/m/Y', $request->data_nascimento);
                    } catch (\Exception $e) {
                        try {
                            $dataNascimento = Carbon::createFromFormat('m/d/Y', $request->data_nascimento);
                        } catch (\Exception $e) {
                            return response()->json([
                                'success' => false,
                                'message' => 'Formato de data inválido. Use YYYY-MM-DD, DD/MM/YYYY ou MM/DD/YYYY'
                            ], Response::HTTP_UNPROCESSABLE_ENTITY);
                        }
                    }
                }
                $dataNascimento = $dataNascimento->format('Y-m-d');
            }

            // Cria o usuário no banco de dados
            $user = User::create([
                'name'             => $request->name,
                'email'            => $request->email,
                'password'         => Hash::make($request->password),
                'telefone_celular' => $request->telefone_celular,
                'data_nascimento'  => $dataNascimento,
                'ativo'            => $request->boolean('ativo', true),
                'position_id'      => $request->position_id,
                'company_id'       => $request->company_id,
                'status_id'        => $request->status_id,
                'situacao_id'      => $request->situacao_id,
            ]);

            return (new UserResource($user->load(['company', 'position'])))
                ->additional([
                    'success' => true,
                    'message' => 'Usuário criado com sucesso'
                ])
                ->response()
                ->setStatusCode(Response::HTTP_CREATED);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erro de validação',
                'errors' => $e->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);

        } catch (\Illuminate\Database\QueryException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erro no banco de dados',
                'error' => $e->getMessage(),
                'error_code' => 'DATABASE_ERROR'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erro ao criar usuário',
                'error'   => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }





    /**
     * Mostrar detalhes de um usuário
     * @urlParam id integer required ID do usuário. Example: 1
     */
    public function show($id)
    {
        try {
            $user = User::with([ 'company',
                'position.permissions',
                'logs'])
                ->find($id);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuário não encontrado'
                ], Response::HTTP_NOT_FOUND);
            }

            return (new UserResource($user))
                ->additional([
                    'success' => true,
                    'message' => 'Usuário recuperado com sucesso'
                ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao recuperar usuário',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Atualizar usuário
     * @urlParam id integer required ID do usuário. Example: 1
     * @bodyParam name string Nome do usuário. Example: João Silva
     * @bodyParam email string Email do usuário. Example: joao@example.com
     * @bodyParam password string Senha. Example: newsecret123
     * @bodyParam password_confirmation string Confirmação da senha. Example: newsecret123
     * @bodyParam company_id integer ID da unidade. Example: 1
     * @bodyParam position_id integer ID do position. Example: 1
     * @bodyParam active boolean Status do usuário. Example: true
     */
    public function update(UserUpdateRequest $request, $id)
    {
        try {
            $user = User::findOrFail($id);

            $data = $request->validated();

            // Remover confirmação de senha se existir
            unset($data['password_confirmation']);

            // Se senha foi enviada, hash e mantém, senão remove para não atualizar
            if (!empty($data['password'])) {
                $data['password'] = Hash::make($data['password']);
            } else {
                unset($data['password']);
            }

            // Atualiza o utilizador com os dados filtrados
            $user->update($data);

            return (new UserResource($user->fresh()->load(['company', 'position', 'logs'])))
                ->additional([
                    'success' => true,
                    'message' => 'Usuário atualizado com sucesso'
                ]);

        } catch (\Exception $e) {
            \Log::error('Update error: '.$e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erro ao atualizar usuário',
                'error' => $e->getMessage()
            ], ResponseAlias::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Remover usuário
     * @urlParam id integer required ID do usuário. Example: 1
     */
    public function destroy($id)
    {
        try {
            $user = User::find($id);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuário não encontrado'
                ], Response::HTTP_NOT_FOUND);
            }

            $user->delete();

            return response()->json([
                'success' => true,
                'message' => 'Usuário removido com sucesso'
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao remover usuário',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Ativar usuário
     * @urlParam id integer required ID do usuário. Example: 1
     */
    public function activate($id)
    {
        try {
            $user = User::find($id);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuário não encontrado'
                ], Response::HTTP_NOT_FOUND);
            }

            $user->update(['active' => true]);

            return (new UserResource($user))
                ->additional([
                    'success' => true,
                    'message' => 'Usuário ativado com sucesso'
                ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao ativar usuário',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Desativar usuário
     * @urlParam id integer required ID do usuário. Example: 1
     */
    public function deactivate($id)
    {
        try {
            $user = User::find($id);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuário não encontrado'
                ], Response::HTTP_NOT_FOUND);
            }

            $user->update(['active' => false]);

            return (new UserResource($user))
                ->additional([
                    'success' => true,
                    'message' => 'Usuário desativado com sucesso'
                ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao desativar usuário',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Upload de foto de perfil
     * @bodyParam foto_perfil file required Arquivo de imagem para foto de perfil. Máximo: 2MB
     */
    public function uploadPhoto(UploadPhotoRequest $request)
    {
        try {
            $user = auth()->user();
            $file = $request->file('foto_perfil');
            $path = "arquivos/foto_perfil/{$user->id}";

            // Remove imagem anterior, se existir
            if ($user->foto_perfil && Storage::disk('public')->exists($user->foto_perfil)) {
                Storage::disk('public')->delete($user->foto_perfil);
            }

            $filePath = $file->storeAs($path, 'foto.png', 'public');

            // Ler o arquivo e converter para base64
            $fileContents = Storage::disk('public')->get($filePath);
            $base64 = base64_encode($fileContents);
            $mimeType = Storage::disk('public')->mimeType($filePath);
            $base64Image = 'data:' . $mimeType . ';base64,' . $base64;

            $user->foto_perfil = $filePath;
            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'Foto de perfil atualizada com sucesso',
                'data' => [
                    'foto_perfil' => $base64Image // Retornando como base64
                ]
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao fazer upload da foto de perfil',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    protected function processarDataNascimento($data)
    {
        if (empty($data)) {
            return null;
        }

        try {
            return Carbon::createFromFormat('Y-m-d', $data)->format('Y-m-d');
        } catch (\Exception $e) {
            try {
                return Carbon::createFromFormat('d/m/Y', $data)->format('Y-m-d');
            } catch (\Exception $e) {
                try {
                    return Carbon::createFromFormat('m/d/Y', $data)->format('Y-m-d');
                } catch (\Exception $e) {
                    throw new \InvalidArgumentException(
                        "Formato de data inválido. Use YYYY-MM-DD, DD/MM/YYYY ou MM/DD/YYYY"
                    );
                }
            }
        }
    }

    protected function processarSenha($password)
    {
        if (empty($password)) {
            throw new \InvalidArgumentException("Senha não pode ser vazia");
        }

        return Hash::make($password);
    }

    protected function criarUsuario($request, $password, $dataNascimento)
    {
        try {
            return User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => $password,
                'unidade_id' => $request->unidade_id ?? 1, // Valor padrão apenas se necessário
                'position_id' => $request->position_id,
                'status_id' => $request->status_id ?? 1, // Valor padrão apenas se necessário
                'situacao_id' => $request->situacao_id ?? 1, // Valor padrão apenas se necessário
                'active' => $request->boolean('active', true),
                'data_nascimento' => $dataNascimento,
                'telefone_celular' => $request->telefone_celular, // Usando o valor do request
            ]);
        } catch (\Exception $e) {
            throw new \RuntimeException("Falha ao criar usuário: " . $e->getMessage());
        }
    }

    /**
     * Aplica todos os filtros à query
     */
    private function applyFilters($query, $request)
    {
        if ($request->filled('ativo')) {
            $query->where('ativo', $request->ativo);
        }

        if ($request->filled('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        if ($request->filled('user_id')) {
            $query->where('id', $request->user_id);
        }

        if ($request->filled('name')) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }

        if ($request->filled('email')) {
            $query->where('email', 'like', '%' . $request->email . '%');
        }

        return $query;
    }
}
