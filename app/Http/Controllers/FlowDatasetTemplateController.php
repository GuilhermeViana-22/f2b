<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\FlowDatasetTemplate;
use App\Http\Resources\FlowDatasetTemplateResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="FlowDatasetTemplate",
 *     description="API endpoints para gerenciamento de templates de validação de datasets"
 * )
 */
class FlowDatasetTemplateController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/flow-dataset-templates",
     *     summary="Lista todos os templates de validação do usuário",
     *     tags={"FlowDatasetTemplate"},
     *     @OA\Parameter(
     *         name="type",
     *         in="query",
     *         description="Filtrar por tipo (json/xml)",
     *         @OA\Schema(type="string", enum={"json", "xml"})
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de templates"
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            if (!$user || !$user->company_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'User have not a Company.',
                    'data' => []
                ], 403);
            }

            $query = FlowDatasetTemplate::where('company_id', $user->company_id);
            
            // Filtrar por tipo se especificado
            if ($request->has('type') && in_array($request->type, ['json', 'xml'])) {
                $query->where('type', $request->type);
            }
            
            $templates = $query->orderBy('created_at', 'desc')->get();
            
            return response()->json([
                'success' => true,
                'data' => FlowDatasetTemplateResource::collection($templates),
                'message' => 'Templates carregados com sucesso'
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao listar templates: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Erro ao carregar templates: ' . $e->getMessage(),
                'data' => []
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/flow-dataset-templates",
     *     summary="Cria um novo template de validação",
     *     tags={"FlowDatasetTemplate"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "type", "rules", "category"},
     *             @OA\Property(property="name", type="string", description="Nome do template"),
     *             @OA\Property(property="type", type="string", enum={"json", "xml"}, description="Tipo do template"),
     *             @OA\Property(property="rules", type="string", description="Regras de validação"),
     *             @OA\Property(property="category", type="string", enum={"selection", "quantities"}, description="Categoria do template")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Template criado com sucesso"
     *     )
     * )
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'type' => 'required|in:json,xml',
                'rules' => 'required|string',
                'category' => 'required|string|in:selection,quantities'
            ]);

            $user = Auth::user();
            if (!$user || !$user->company_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuário não está associado a uma empresa',
                    'data' => null
                ], 403);
            }

            $template = FlowDatasetTemplate::create([
                'name' => $request->name,
                'type' => $request->type,
                'rules' => $request->rules,
                'category' => $request->category,
                'company_id' => $user->company_id
            ]);
            
            return response()->json([
                'success' => true,
                'data' => new FlowDatasetTemplateResource($template),
                'message' => 'Template criado com sucesso'
            ], 201);
        } catch (\Exception $e) {
            Log::error('Erro ao criar template: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Erro ao criar template: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/flow-dataset-templates/{id}",
     *     summary="Exibe um template específico",
     *     tags={"FlowDatasetTemplate"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Template encontrado"
     *     )
     * )
     */
    public function show(int $id): JsonResponse
    {
        try {
            $user = Auth::user();
            if (!$user || !$user->company_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuário não está associado a uma empresa',
                    'data' => null
                ], 403);
            }

            $template = FlowDatasetTemplate::where('id', $id)
                ->where('company_id', $user->company_id)
                ->firstOrFail();
            
            return response()->json([
                'success' => true,
                'data' => new FlowDatasetTemplateResource($template),
                'message' => 'Template encontrado com sucesso'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Template não encontrado',
                'data' => null
            ], 404);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/flow-dataset-templates/{id}",
     *     summary="Atualiza um template",
     *     tags={"FlowDatasetTemplate"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", description="Nome do template"),
     *             @OA\Property(property="type", type="string", enum={"json", "xml"}, description="Tipo do template"),
     *             @OA\Property(property="rules", type="string", description="Regras de validação"),
     *             @OA\Property(property="category", type="string", enum={"selection", "quantities"}, description="Categoria do template")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Template atualizado com sucesso"
     *     )
     * )
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $request->validate([
                'name' => 'string|max:255',
                'type' => 'in:json,xml',
                'rules' => 'string',
                'category' => 'string|in:selection,quantities'
            ]);

            $user = Auth::user();
            if (!$user || !$user->company_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuário não está associado a uma empresa',
                    'data' => null
                ], 403);
            }

            $template = FlowDatasetTemplate::where('id', $id)
                ->where('company_id', $user->company_id)
                ->firstOrFail();
            
            $template->update($request->only(['name', 'type', 'rules', 'category']));
            
            return response()->json([
                'success' => true,
                'data' => new FlowDatasetTemplateResource($template),
                'message' => 'Template atualizado com sucesso'
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao atualizar template: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Erro ao atualizar template: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/flow-dataset-templates/{id}",
     *     summary="Remove um template",
     *     tags={"FlowDatasetTemplate"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Template removido com sucesso"
     *     )
     * )
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $user = Auth::user();
            if (!$user || !$user->company_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuário não está associado a uma empresa',
                    'data' => null
                ], 403);
            }

            $template = FlowDatasetTemplate::where('id', $id)
                ->where('company_id', $user->company_id)
                ->firstOrFail();
            
            $template->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Template removido com sucesso',
                'data' => null
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Template não encontrado',
                'data' => null
            ], 404);
        }
    }
}
