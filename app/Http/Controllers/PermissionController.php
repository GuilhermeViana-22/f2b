<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\Position;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;

/**
 * @OA\Tag(
 *     name="Permissões",
 *     description="Gerenciamento de permissões"
 * )
 */
class PermissionController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/permissions",
     *     tags={"Permissões"},
     *     summary="Listar todas as permissões",
     *     @OA\Response(
     *         response=200,
     *         description="Lista de permissões retornada com sucesso"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erro interno no servidor"
     *     )
     * )
     */
    public function index()
    {
        try {
            $permissions = Permission::all();

            return response()->json([
                'success' => true,
                'message' => 'Permissões recuperadas com sucesso',
                'data' => $permissions
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Falha ao recuperar permissões',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/permissions",
     *     tags={"Permissões"},
     *     summary="Criar uma nova permissão",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string", example="editar_usuarios"),
     *             @OA\Property(property="description", type="string", example="Permissão para editar usuários")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Permissão criada com sucesso"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Dados inválidos"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erro interno no servidor"
     *     )
     * )
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255|unique:permissions',
                'description' => 'nullable|string|max:500'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dados inválidos',
                    'errors' => $validator->errors()
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $permission = Permission::create($validator->validated());

            return response()->json([
                'success' => true,
                'message' => 'Permissão criada com sucesso',
                'data' => $permission
            ], Response::HTTP_CREATED);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Falha ao criar permissão',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
