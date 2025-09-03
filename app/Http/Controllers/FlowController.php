<?php

namespace App\Http\Controllers;

use App\Models\Flow;
use App\Models\FlowStep;
use App\Models\FlowTag;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="Flow",
 *     description="API endpoints para gerenciamento de flows"
 * )
 */
class FlowController extends Controller
{
    /**
     * Obter o company_id do usuário autenticado
     */
    private function getUserCompanyId(): ?int
    {
        $user = Auth::user();
        return $user ? $user->company_id : null;
    }
    
    /**
     * Validar se o modelo pertence à empresa do usuário
     */
    private function validateCompanyAccess($model): bool
    {
        if (!$model) {
            return false;
        }
        
        $userCompanyId = $this->getUserCompanyId();
        if (!$userCompanyId) {
            return false;
        }
        
        return isset($model->company_id) && $model->company_id === $userCompanyId;
    }
    
    /**
     * Resposta padrão para acesso negado por empresa
     */
    private function companyAccessDeniedResponse(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Access denied. You can only access resources from your company.',
            'error' => 'COMPANY_ACCESS_DENIED'
        ], 403);
    }

    // ===================================================
    // CRUD FLOWS
    // ===================================================

    /**
     * @OA\Get(
     *     path="/api/flows",
     *     summary="Lista todos os flows",
     *     tags={"Flow"},
     *     @OA\Response(
     *         response=200,
     *         description="Lista de flows"
     *     )
     * )
     */
    public function index(): JsonResponse
    {
        $userCompanyId = $this->getUserCompanyId();
        if (!$userCompanyId) {
            return response()->json([], 200); // Retorna vazio se não tem company_id
        }
        
        $flows = Flow::where('company_id', $userCompanyId)->get();
        $flows->load(['steps']); // eager loading sem order

        foreach ($flows as $flow) {
            // aplica ordenação manual depois
            $orderedSteps = $flow->steps()->orderBy('order')->get();
            
            // Carregar tags para cada step usando o accessor
            foreach ($orderedSteps as $step) {
                // Força o carregamento das tags através do accessor
                $step->append('tags');
            }
            
            $flow->setRelation('steps', $orderedSteps);
            
            // Adicionar status field para compatibilidade com frontend
            $flow->status = $flow->active ? 'active' : 'inactive';
        }

        return response()->json($flows);
    }

    /**
     * @OA\Get(
     *     path="/api/flows/active",
     *     summary="Busca o flow ativo atual",
     *     tags={"Flow"},
     *     @OA\Response(
     *         response=200,
     *         description="Flow ativo encontrado",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", type="object"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Nenhum flow ativo encontrado"
     *     )
     * )
     */
    public function getActiveFlow(): JsonResponse
    {
        try {
            $userCompanyId = $this->getUserCompanyId();
            if (!$userCompanyId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nenhum flow ativo encontrado',
                    'data' => null
                ], 404);
            }
            
            $activeFlow = Flow::where('active', true)
                             ->where('company_id', $userCompanyId)
                             ->first();
            
            if (!$activeFlow) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nenhum flow ativo encontrado',
                    'data' => null
                ], 404);
            }
            
            // Carregar steps com ordenação
            $activeFlow->load(['steps' => function($query) {
                $query->orderBy('order');
            }]);
            
            // Carregar tags para cada step
            foreach ($activeFlow->steps as $step) {
                $step->append('tags');
            }
            
            // Adicionar status field para compatibilidade com frontend
            $activeFlow->status = $activeFlow->active ? 'active' : 'inactive';
            
            return response()->json([
                'success' => true,
                'data' => $activeFlow,
                'message' => 'Flow ativo encontrado com sucesso'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Erro ao buscar flow ativo: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar flow ativo: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/flows",
     *     summary="Cria um novo flow",
     *     tags={"Flow"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "user_id"},
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="note", type="string"),
     *             @OA\Property(property="active", type="boolean"),
     *             @OA\Property(property="user_id", type="integer")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Flow criado com sucesso"
     *     )
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'note' => 'nullable|string',
            'active' => 'boolean',
            'user_id' => 'required|integer',
            'external_integration_id' => 'nullable|string',
            'webhook_dataset_id' => 'nullable|string'
        ]);
        
        $userCompanyId = $this->getUserCompanyId();
        if (!$userCompanyId) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to determine user company.',
            ], 400);
        }

        $flowData = array_merge($request->all(), [
            'active' => false,
            'company_id' => $userCompanyId
        ]);
        
        $flow = Flow::create($flowData);
        
        return response()->json($flow, 201);
    }

    /**
     * @OA\Get(
     *     path="/api/flows/{id}",
     *     summary="Exibe um flow específico",
     *     tags={"Flow"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Flow encontrado"
     *     )
     * )
     */
    public function show(string $id): JsonResponse
    {
        $flow = Flow::with(['steps' => function($query) {
            $query->orderBy('order');
        }])->findOrFail($id);
        
        // Validar acesso por company_id
        if (!$this->validateCompanyAccess($flow)) {
            return $this->companyAccessDeniedResponse();
        }
        
        // Carregar tags para cada step usando o accessor
        foreach ($flow->steps as $step) {
            // Força o carregamento das tags através do accessor
            $step->append('tags');
        }
        
        return response()->json($flow);
    }

    /**
     * @OA\Put(
     *     path="/api/flows/{id}",
     *     summary="Atualiza um flow",
     *     tags={"Flow"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="note", type="string"),
     *             @OA\Property(property="active", type="boolean"),
     *             @OA\Property(property="user_id", type="integer")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Flow atualizado com sucesso"
     *     )
     * )
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'name' => 'string|max:255',
            'note' => 'nullable|string',
            'active' => 'boolean',
            'user_id' => 'integer',
            'external_integration_id' => 'nullable|string',
            'webhook_dataset_id' => 'nullable|string'
        ]);

        $flow = Flow::findOrFail($id);
        
        // Validar acesso por company_id
        if (!$this->validateCompanyAccess($flow)) {
            return $this->companyAccessDeniedResponse();
        }
        
        $flow->update($request->all());
        return response()->json($flow);
    }

    /**
     * @OA\Delete(
     *     path="/api/flows/{id}",
     *     summary="Remove um flow",
     *     tags={"Flow"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="Flow removido com sucesso"
     *     )
     * )
     */
    public function destroy(string $id): JsonResponse
    {
        $flow = Flow::findOrFail($id);
        
        // Validar acesso por company_id
        if (!$this->validateCompanyAccess($flow)) {
            return $this->companyAccessDeniedResponse();
        }
        
        // Remover steps relacionados
        $flow->steps()->delete();
        
        $flow->delete();
        return response()->json(null, 204);
    }

    /**
     * @OA\Patch(
     *     path="/api/flows/{id}/toggle-status",
     *     summary="Alterna o status de um flow (apenas um pode estar ativo)",
     *     tags={"Flow"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Status do flow alterado com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Flow")),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function toggleStatus(string $id): JsonResponse
    {
        try {
            $userCompanyId = $this->getUserCompanyId();
            if (!$userCompanyId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unable to determine user company.'
                ], 400);
            }
            
            // Verificar se o flow existe e pertence à empresa
            $flow = Flow::where('_id', $id)
                       ->where('company_id', $userCompanyId)
                       ->first();
                       
            if (!$flow) {
                return response()->json([
                    'success' => false,
                    'message' => 'Flow não encontrado'
                ], 404);
            }
            
            $currentStatus = $flow->active ?? false;
            
            if (!$currentStatus) {
                // Ativando este flow - desativar todos os outros da empresa primeiro
                Flow::where('_id', '!=', $id)
                    ->where('company_id', $userCompanyId)
                    ->update(['active' => false]);
                $flow->update(['active' => true]);
            } else {
                // Desativando este flow
                $flow->update(['active' => false]);
            }
            
            // Retornar apenas flows da empresa do usuário
            $allFlows = Flow::where('company_id', $userCompanyId)->get();
            
            // Adicionar status field para compatibilidade com frontend
            $allFlows->each(function ($flow) {
                $flow->status = $flow->active ? 'active' : 'inactive';
            });
            
            return response()->json([
                'success' => true,
                'data' => $allFlows,
                'message' => 'Status do flow alterado com sucesso'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Erro ao alternar status do flow: ' . $e->getMessage());
            Log::error('Flow ID: ' . $id);
            
            return response()->json([
                'success' => false,
                'message' => 'Erro ao alternar status do flow: ' . $e->getMessage()
            ], 500);
        }
    }

    // ===================================================
    // CRUD FLOW STEPS
    // ===================================================

    /**
     * @OA\Get(
     *     path="/api/flows/{flowId}/steps",
     *     summary="Lista todos os steps de um flow",
     *     tags={"Flow"},
     *     @OA\Parameter(
     *         name="flowId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de steps"
     *     )
     * )
     */
    public function getFlowSteps(string $flowId): JsonResponse
    {
        $steps = FlowStep::where('flow_id', $flowId)->orderBy('order')->get();
        return response()->json($steps);
    }

    /**
     * @OA\Post(
     *     path="/api/flows/{flowId}/steps",
     *     summary="Cria um novo step em um flow",
     *     tags={"Flow"},
     *     @OA\Parameter(
     *         name="flowId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "expression", "order", "user_id"},
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="expression", type="string"),
     *             @OA\Property(property="order", type="integer"),
     *             @OA\Property(property="active", type="boolean"),
     *             @OA\Property(property="user_id", type="integer")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Step criado com sucesso"
     *     )
     * )
     */
    public function storeFlowStep(Request $request, string $flowId): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'expression' => 'required|string',
            'order' => 'required|integer',
            'active' => 'boolean',
            'user_id' => 'required|integer'
        ]);

        $stepData = $request->all();
        $stepData['flow_id'] = $flowId;

        $step = FlowStep::create($stepData);
        return response()->json($step, 201);
    }

    /**
     * @OA\Get(
     *     path="/api/flow-steps/{id}",
     *     summary="Exibe um step específico",
     *     tags={"Flow"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Step encontrado"
     *     )
     * )
     */
    public function showFlowStep(string $id): JsonResponse
    {
        $step = FlowStep::with(['flow'])->findOrFail($id);
        return response()->json($step);
    }

    /**
     * @OA\Put(
     *     path="/api/flow-steps/{id}",
     *     summary="Atualiza um step",
     *     tags={"Flow"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="expression", type="string"),
     *             @OA\Property(property="order", type="integer"),
     *             @OA\Property(property="active", type="boolean"),
     *             @OA\Property(property="user_id", type="integer")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Step atualizado com sucesso"
     *     )
     * )
     */
    public function updateFlowStep(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'name' => 'string|max:255',
            'expression' => 'string',
            'order' => 'integer',
            'active' => 'boolean',
            'user_id' => 'integer'
        ]);

        $step = FlowStep::findOrFail($id);
        $step->update($request->all());
        return response()->json($step);
    }

    /**
     * @OA\Delete(
     *     path="/api/flow-steps/{id}",
     *     summary="Remove um step",
     *     tags={"Flow"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="Step removido com sucesso"
     *     )
     * )
     */
    public function destroyFlowStep(string $id): JsonResponse
    {
        $step = FlowStep::findOrFail($id);
        
        // Step será removido (tags estão em array, não precisam ser removidas separadamente)
        
        $step->delete();
        return response()->json(null, 204);
    }

    // ===================================================
    // CRUD FLOW TAGS
    // ===================================================

    /**
     * @OA\Get(
     *     path="/api/flow-steps/{stepId}/tags",
     *     summary="Lista todas as tags de um step",
     *     tags={"Flow"},
     *     @OA\Parameter(
     *         name="stepId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de tags"
     *     )
     * )
     */
    public function getFlowTags(string $stepId): JsonResponse
    {
        $tags = FlowTag::where('flow_step_id', $stepId)->get();
        return response()->json($tags);
    }

    /**
     * @OA\Post(
     *     path="/api/flow-steps/{stepId}/tags",
     *     summary="Cria uma nova tag em um step",
     *     tags={"Flow"},
     *     @OA\Parameter(
     *         name="stepId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "color"},
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="color", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Tag criada com sucesso"
     *     )
     * )
     */
    public function storeFlowTag(Request $request, string $stepId): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'color' => 'required|string|max:7'
        ]);

        $tagData = $request->all();
        $tagData['flow_step_id'] = $stepId;

        $tag = FlowTag::create($tagData);
        return response()->json($tag, 201);
    }

    /**
     * @OA\Get(
     *     path="/api/flow-tags/{id}",
     *     summary="Exibe uma tag específica",
     *     tags={"Flow"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Tag encontrada"
     *     )
     * )
     */
    public function showFlowTag(string $id): JsonResponse
    {
        $tag = FlowTag::with('flowStep')->findOrFail($id);
        return response()->json($tag);
    }

    /**
     * @OA\Put(
     *     path="/api/flow-tags/{id}",
     *     summary="Atualiza uma tag",
     *     tags={"Flow"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="color", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Tag atualizada com sucesso"
     *     )
     * )
     */
    public function updateFlowTag(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'name' => 'string|max:255',
            'color' => 'string|max:7'
        ]);

        $tag = FlowTag::findOrFail($id);
        $tag->update($request->all());
        return response()->json($tag);
    }

    /**
     * @OA\Delete(
     *     path="/api/flow-tags/{id}",
     *     summary="Remove uma tag",
     *     tags={"Flow"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="Tag removida com sucesso"
     *     )
     * )
     */
    public function destroyFlowTag(string $id): JsonResponse
    {
        $tag = FlowTag::findOrFail($id);
        $tag->delete();
        return response()->json(null, 204);
    }
}
