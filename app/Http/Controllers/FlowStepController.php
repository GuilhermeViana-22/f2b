<?php

namespace App\Http\Controllers;

use App\Models\Flow;
use App\Models\FlowStep;
use App\Models\FlowStepHistory;
use App\Models\FlowTag;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use OpenApi\Annotations as OA;

class FlowStepController extends Controller
{
    // ===================================================
    // CRUD FLOWS
    // ===================================================

    public function index(): JsonResponse
    {
        $flow_steps = FlowStep::all();
        $flow_steps->load(['tags']); 

        foreach ($flow_steps as $flow_step) {
            // aplica ordenação manual depois
            $orderedSteps = $flow_step->steps()->orderBy('order')->get();
            $flow_step->setRelation('tags', $orderedSteps);
        }

        return response()->json($flow_steps);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'expression' => 'nullable|string',
            'description' => 'nullable|string|max:1000',
            'active' => 'boolean',
            'flow_id' => 'string',
            'user_id' => 'required|integer',
            'tag_ids' => 'array',
            'tag_ids.*' => 'string'
        ]);

        $flow_step = FlowStep::create($request->all());
        
        // Criar snapshot inicial apenas se houver expression
        if (!empty($flow_step->expression) && trim($flow_step->expression) !== '') {
            FlowStepHistory::createSnapshot(
                $flow_step,
                'create',
                'Initial version created with expression',
                $request->user_id
            );
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Flow step created successfully',
            'data' => $flow_step
        ], 201);
    }

    
    public function show(string $id): JsonResponse
    {
        $flow_step = FlowStep::with(['tags'])->findOrFail($id);
        return response()->json($flow_step);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'expression' => 'nullable|string',
            'description' => 'nullable|string|max:1000',
            'order' => 'integer',
            'active' => 'boolean',
            'flow_id' => 'string',
            'user_id' => 'integer',
            'tag_ids' => 'array',
            'tag_ids.*' => 'string'
        ]);

        $flow_step = FlowStep::findOrFail($id);
        
        // Armazenar os dados originais para comparação
        $originalData = $flow_step->toArray();
        
        // Verificar se a expression foi alterada
        $needsVersioning = false;
        $changeDescription = '';
        
        if ($request->has('expression')) {
            $newExpression = trim($request->expression ?? '');
            $oldExpression = trim($flow_step->expression ?? '');
            
            // Se a expression mudou E não está vazia, criar versão
            if ($newExpression !== $oldExpression && !empty($newExpression)) {
                $needsVersioning = true;
                $changeDescription = 'Expression updated';
            }
            // Se removeu uma expression existente, também criar versão
            elseif (!empty($oldExpression) && empty($newExpression)) {
                $needsVersioning = true;
                $changeDescription = 'Expression removed';
            }
        }
        
        $flow_step->update($request->all());
        
        // Criar snapshot apenas se necessário
        if ($needsVersioning) {
            FlowStepHistory::createSnapshot(
                $flow_step->fresh(),
                'update',
                $changeDescription,
                $request->user_id ?? $flow_step->user_id
            );
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Flow step updated successfully',
            'data' => $flow_step,
            'version_created' => $needsVersioning
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        $flow_step = FlowStep::findOrFail($id);
        
        $flow_step->delete();
        return response()->json(null, 204);
    }

    /**
     * Reorder flow steps
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function reorder(Request $request): JsonResponse
    {
        try {
            foreach ($request->steps as $stepData) {
                $flowStep = FlowStep::findOrFail($stepData['id']);
                $flowStep->update(['order' => $stepData['order']]);
            }

            return response()->json([
                'message' => 'Steps reordered successfully',
                'steps' => $request->steps
            ]);
        } catch (\Exception $e) {
            Log::error('Error reordering flow steps: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error reordering steps',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // ===================================================
    // VERSION CONTROL METHODS
    // ===================================================

    /**
     * Get version history for a specific FlowStep
     */
    public function getVersionHistory(string $id): JsonResponse
    {
        try {
            $flowStep = FlowStep::findOrFail($id);
            
            $history = FlowStepHistory::where('flow_step_id', $id)
                ->orderedVersions()
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'flow_step' => $flowStep,
                    'history' => $history,
                    'total_versions' => $history->count(),
                    'current_version' => $history->where('is_current', true)->first()
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting version history: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error getting version history',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get specific version details
     */
    public function getVersion(string $stepId, string $versionId): JsonResponse
    {
        try {
            $version = FlowStepHistory::where('flow_step_id', $stepId)
                ->where('_id', $versionId)
                ->firstOrFail();

            return response()->json([
                'success' => true,
                'data' => $version
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Version not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Compare two versions
     */
    public function compareVersions(string $stepId, Request $request): JsonResponse
    {
        $request->validate([
            'version_from' => 'required|string',
            'version_to' => 'required|string'
        ]);

        try {
            $versionFrom = FlowStepHistory::where('flow_step_id', $stepId)
                ->where('_id', $request->version_from)
                ->firstOrFail();

            $versionTo = FlowStepHistory::where('flow_step_id', $stepId)
                ->where('_id', $request->version_to)
                ->firstOrFail();

            $changes = $versionTo->compareWith($versionFrom);

            return response()->json([
                'success' => true,
                'data' => [
                    'version_from' => $versionFrom,
                    'version_to' => $versionTo,
                    'changes' => $changes,
                    'has_changes' => !empty($changes)
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error comparing versions',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Restore a specific version
     */
    public function restoreVersion(string $stepId, string $versionId): JsonResponse
    {
        try {
            $version = FlowStepHistory::where('flow_step_id', $stepId)
                ->where('_id', $versionId)
                ->firstOrFail();

            $restoredFlowStep = $version->restore();

            return response()->json([
                'success' => true,
                'message' => 'Version restored successfully',
                'data' => [
                    'flow_step' => $restoredFlowStep,
                    'restored_from_version' => $version->version
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error restoring version: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error restoring version',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get version statistics
     */
    public function getVersionStats(string $stepId): JsonResponse
    {
        try {
            $stats = FlowStepHistory::getChangeStats($stepId);
            $totalVersions = FlowStepHistory::byFlowStep($stepId)->count();
            $latestVersion = FlowStepHistory::byFlowStep($stepId)
                ->orderedVersions()
                ->first();

            return response()->json([
                'success' => true,
                'data' => [
                    'total_versions' => $totalVersions,
                    'change_stats' => $stats,
                    'latest_version' => $latestVersion,
                    'first_created' => FlowStepHistory::byFlowStep($stepId)
                        ->orderBy('version', 'asc')
                        ->first()?->created_at
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error getting version statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
