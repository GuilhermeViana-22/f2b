<?php

namespace App\Http\Controllers;

use App\Http\Resources\Positions\PositionResource;
use App\Models\Position;
use Illuminate\Http\Response;
use Illuminate\Http\Request;
use App\Http\Requests\Position\PositionStoreRequest;
use App\Http\Requests\Position\PositionUpdateRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

class PositionController extends Controller
{

    /**
     * @OA\Get(
     *     path="/api/positions",
     *     summary="Lists all positions registered in the system",
     *     description="Returns an array with all system positions",
     *     tags={"positions"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful position listing",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="success",
     *                 type="boolean",
     *                 example=true
     *             ),
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Positions retrieved successfully"
     *             ),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="position", type="string", example="Gerente"),
     *                     @OA\Property(property="created_at", type="string", format="date-time"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Failed to retrieve positions"),
     *             @OA\Property(property="error", type="string", example="Detailed error message")
     *         )
     *     )
     * )
     */
    public function index()
    {
        try {
            $positions = Position::with('permissions')
                ->paginate(10); // Define quantos itens por página

            return response()->json([
                'success' => true,
                'message' => 'Positions retrieved successfully',
                'data' => PositionResource::collection($positions),
                'pagination' => [
                    'total' => $positions->total(),
                    'per_page' => $positions->perPage(),
                    'current_page' => $positions->currentPage(),
                    'last_page' => $positions->lastPage(),
                    'from' => $positions->firstItem(),
                    'to' => $positions->lastItem()
                ]
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve positions',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/positions",
     *     summary="Create a new position",
     *     description="Creates a new position with associated permissions",
     *     tags={"positions"},
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"position", "permissions"},
     *             @OA\Property(property="position", type="string", example="Analista de TI"),
     *             @OA\Property(property="level_hierarchical", type="integer", example=2),
     *             @OA\Property(property="department", type="string", example="TI"),
     *             @OA\Property(property="description", type="string", example="Analista de sistemas"),
     *             @OA\Property(
     *                 property="permissions",
     *                 type="array",
     *                 @OA\Items(type="integer", example=1)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Position created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Position created successfully"),
     *             @OA\Property(property="data", type="object", ref="#/components/schemas/Position")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Invalid data"),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 example={"position": {"The position field is required."}}
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Failed to create position"),
     *             @OA\Property(property="error", type="string", example="Detailed error message")
     *         )
     *     )
     * )
     */
    public function store(PositionStoreRequest $request)
    {
        try {
            $validated = $request->validated();

            DB::beginTransaction();

            // Cria a posição
            $position = Position::create([
                'position' => $validated['position'],
                'level_hierarchical' => $validated['level_hierarchical'],
                'department' => $validated['department'],
                'description' => $validated['description'] ?? null,
            ]);

            // Associa as permissões à posição
            if (isset($validated['permissions']) && !empty($validated['permissions'])) {
                $position->permissions()->sync($validated['permissions']);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Position created successfully',
                'data' => new PositionResource($position->load('permissions'))
            ], Response::HTTP_CREATED);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create position',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/positions/{id}",
     *     summary="Get a specific position",
     *     description="Returns details of a specific position",
     *     tags={"positions"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Position retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Position retrieved successfully"),
     *             @OA\Property(property="data", type="object", ref="#/components/schemas/Position")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Position not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Position not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Failed to retrieve position"),
     *             @OA\Property(property="error", type="string", example="Detailed error message")
     *         )
     *     )
     * )
     */
    public function show($id)
    {
        try {
            $position = Position::with('permissions')->find($id);

            if (!$position) {
                return response()->json([
                    'success' => false,
                    'message' => 'Position not found'
                ], Response::HTTP_NOT_FOUND);
            }

            return response()->json([
                'success' => true,
                'message' => 'Position retrieved successfully',
                'data' => new PositionResource($position)
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve position',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/positions/{id}",
     *     summary="Update a position",
     *     description="Updates an existing position",
     *     tags={"positions"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="position", type="string", example="Analista de TI Jr"),
     *             @OA\Property(property="level_hierarchical", type="integer", example=1),
     *             @OA\Property(property="department", type="string", example="TI"),
     *             @OA\Property(property="description", type="string", example="Analista júnior"),
     *             @OA\Property(
     *                 property="permissions",
     *                 type="array",
     *                 @OA\Items(type="integer", example=1)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Position updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Position updated successfully"),
     *             @OA\Property(property="data", type="object", ref="#/components/schemas/Position")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Position not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Position not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Invalid data"),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 example={"position": {"The position field is required."}}
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Failed to update position"),
     *             @OA\Property(property="error", type="string", example="Detailed error message")
     *         )
     *     )
     * )
     */


    public function update(PositionUpdateRequest $request, $id)
    {
        try {
            $position = Position::find($id);

            if (!$position) {
                return response()->json([
                    'success' => false,
                    'message' => 'Position not found'
                ], Response::HTTP_NOT_FOUND);
            }

            $validated = $request->validated();

            DB::beginTransaction();

            // Update basic position data
            $position->update([
                'position' => $validated['position'],
                'level_hierarchical' => $validated['level_hierarchical'],
                'department' => $validated['department'],
                'description' => $validated['description'] ?? null,
            ]);

            // Atualiza as permissões da posição
            if (isset($validated['permissions'])) {
                $position->permissions()->sync($validated['permissions']);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Position updated successfully',
                'data' => new PositionResource($position->load('permissions'))
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update position',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/positions/{id}",
     *     summary="Delete a position",
     *     description="Deletes a position from the system",
     *     tags={"positions"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Position deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Position deleted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Position not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Position not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Failed to delete position"),
     *             @OA\Property(property="error", type="string", example="Detailed error message")
     *         )
     *     )
     * )
     */
    public function destroy($id)
    {
        try {
            $position = Position::find($id);

            if (!$position) {
                return response()->json([
                    'success' => false,
                    'message' => 'Position not found'
                ], Response::HTTP_NOT_FOUND);
            }

            $position->delete();

            return response()->json([
                'success' => true,
                'message' => 'Position deleted successfully'
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete position',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Synchronizes permissions of a position
     */
    public function syncPermissions(Request $request, $id)
    {
        try {
            $data = $request->validate([
                'permission_ids' => 'required|array',
                'permission_ids.*' => 'exists:permissions,id'
            ]);

            $position = Position::find($id);
            if (!$position) {
                return response()->json([
                    'success' => false,
                    'message' => 'Position not found'
                ], Response::HTTP_NOT_FOUND);
            }

            $position->permissions()->sync($data['permission_ids']);

            return response()->json([
                'success' => true,
                'message' => 'Permissions updated successfully',
                'data' => new PositionResource($position->load('permissions'))
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update permissions',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // Adicionar o método logAccess igual ao AuthController, se não existir
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
            \Log::error('Erro ao registrar log de acesso: ' . $e->getMessage());
        }
    }



    public function listPermissions(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Usuário não autenticado'
            ], 401);
        }

        if (!$user->position) {
            return response()->json([
                'success' => false,
                'message' => 'Cargo não encontrado'
            ], 404);
        }

        $permissions = $user->position->permissions()
            ->select('permissions.id', 'permissions.name')
            ->get();

        return response()->json([
            'success' => true,
            'permissions' => $permissions
        ]);
    }

}

