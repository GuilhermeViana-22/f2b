<?php

namespace App\Http\Controllers;

use App\Models\FlowTag;
use App\Models\FlowStep;
use Illuminate\Http\Request;
use App\Http\Resources\Flows\FlowTagResource;

class FlowTagController extends Controller
{
    // Retorna todas as FlowTags
    public function index()
    {
        try {
            $tags = FlowTag::all();
            return response()->json([
                'success' => true,
                'data' => FlowTagResource::collection($tags)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching flow tags: ' . $e->getMessage()
            ], 500);
        }
    }

    // Cria uma nova FlowTag
    public function store(Request $request)
    {
        try {
            $data = $request->validate([
                'name' => 'required|string|max:255',
                'color' => 'nullable|string|max:7|regex:/^#[0-9A-Fa-f]{6}$/',
                'description' => 'nullable|string|max:500',
            ]);

            // Define cor padrão se não fornecida
            if (!isset($data['color'])) {
                $data['color'] = '#6b7280';
            }

            $tag = FlowTag::create($data);

            return response()->json([
                'success' => true,
                'message' => 'Flow tag created successfully',
                'data' => new FlowTagResource($tag)
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating flow tag: ' . $e->getMessage()
            ], 500);
        }
    }

    // Retorna uma FlowTag específica
    public function show($id)
    {
        try {
            $tag = FlowTag::findOrFail($id);
            return response()->json([
                'success' => true,
                'data' => new FlowTagResource($tag)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Flow tag not found'
            ], 404);
        }
    }

    // Atualiza uma FlowTag
    public function update(Request $request, $id)
    {
        try {
            $tag = FlowTag::findOrFail($id);

            $data = $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'color' => 'nullable|string|max:7|regex:/^#[0-9A-Fa-f]{6}$/',
                'description' => 'nullable|string|max:500',
            ]);

            $tag->update($data);

            return response()->json([
                'success' => true,
                'message' => 'Flow tag updated successfully',
                'data' => new FlowTagResource($tag)
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating flow tag: ' . $e->getMessage()
            ], 500);
        }
    }

    // Remove uma FlowTag
    public function destroy($id)
    {
        try {
            $tag = FlowTag::findOrFail($id);
            
            // Remove a tag de todos os steps que a utilizam
            $steps = FlowStep::where('tag_ids', $id)->get();
            foreach ($steps as $step) {
                $step->removeTag($id);
            }
            
            $tag->delete();

            return response()->json([
                'success' => true,
                'message' => 'Flow tag deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting flow tag: ' . $e->getMessage()
            ], 500);
        }
    }

    // Busca tags por FlowStep ID
    public function getByStepId($stepId)
    {
        try {
            $step = FlowStep::findOrFail($stepId);
            $tags = $step->tags;
            
            return response()->json([
                'success' => true,
                'data' => FlowTagResource::collection($tags)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching tags for step: ' . $e->getMessage()
            ], 500);
        }
    }

    // Associa uma tag a um flow step
    public function attachToStep($stepId, $tagId)
    {
        try {
            $step = FlowStep::findOrFail($stepId);
            $tag = FlowTag::findOrFail($tagId);

            $step->addTag($tagId);

            return response()->json([
                'success' => true,
                'message' => 'Tag attached to step successfully',
                'data' => new FlowTagResource($tag)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error attaching tag to step: ' . $e->getMessage()
            ], 500);
        }
    }

    // Remove a associação de uma tag de um flow step
    public function detachFromStep($stepId, $tagId)
    {
        try {
            $step = FlowStep::findOrFail($stepId);
            $tag = FlowTag::findOrFail($tagId);

            $step->removeTag($tagId);

            return response()->json([
                'success' => true,
                'message' => 'Tag detached from step successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error detaching tag from step: ' . $e->getMessage()
            ], 500);
        }
    }

    // Atualiza as tags de um flow step
    public function updateStepTags(Request $request, $stepId)
    {
        try {
            $step = FlowStep::findOrFail($stepId);

            $data = $request->validate([
                'tagIds' => 'required|array'
            ]);

            // Verifica se todas as tags existem
            $existingTags = FlowTag::whereIn('_id', $data['tagIds'])->count();
            if ($existingTags !== count($data['tagIds'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'One or more tags do not exist'
                ], 400);
            }

            $step->syncTags($data['tagIds']);

            return response()->json([
                'success' => true,
                'message' => 'Step tags updated successfully',
                'data' => FlowTagResource::collection($step->tags)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating step tags: ' . $e->getMessage()
            ], 500);
        }
    }
}

