<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\Selection;
use App\Models\SelectionChoice;
use App\Http\Resources\Selections\SelectionResource;
use Illuminate\Support\Facades\Validator;


class SelectionController extends Controller
{
    /**
     * Show's selections data
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        
        try {
            $selections = Selection::with('choices')
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Selections successfully recovered',
                'data' => $selections
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error when searching selections',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            // Validação básica
            $data = $request->validate([
                'jobNo' => 'required|string',
                'client' => 'required|string',
                'houseType' => 'nullable|string',
                'elevation' => 'nullable|string',
                'specification' => 'nullable|string',
                'lotAddress' => 'nullable|string',
                'choices' => 'required|array|min:1',
            ]);

            // Cria a Selection
            $selection = Selection::create([
                'jobNo' => $data['jobNo'],
                'client' => $data['client'],
                'houseType' => $data['houseType'] ?? null,
                'elevation' => $data['elevation'] ?? null,
                'specification' => $data['specification'] ?? null,
                'lotAddress' => $data['lotAddress'] ?? null,
                'choices_count' => count($data['choices'])
            ]);

            $choices = $selection->choices()->createMany($data['choices']);

            return response()->json([
                'success' => true,
                'message' => 'Seleção criada com sucesso',
                'data' => $selection->load('choices'),
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao criar seleção',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $selection = Selection::with('choices')->find($id);

            if (!$selection) {
                return response()->json([
                    'success' => false,
                    'message' => 'Selection not found'
                ], Response::HTTP_NOT_FOUND);
            }

            return response()->json([
                'success' => true,
                'message' => 'Selection retrieved successfully',
                'data' => new SelectionResource($selection)
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve Selection',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            // Validação dos dados
            $data = $request->validate([
                'jobNo' => 'required|string',
                'client' => 'required|string',
                'houseType' => 'nullable|string',
                'elevation' => 'nullable|string',
                'specification' => 'nullable|string',
                'lotAddress' => 'nullable|string',
                'choices' => 'required|array|min:1',
            ]);

            // Busca a seleção
            $selection = Selection::findOrFail($id);

            // Atualiza os dados principais da seleção
            $selection->update([
                'jobNo' => $data['jobNo'],
                'client' => $data['client'],
                'houseType' => $data['houseType'] ?? null,
                'elevation' => $data['elevation'] ?? null,
                'specification' => $data['specification'] ?? null,
                'lotAddress' => $data['lotAddress'] ?? null,
                'choices_count' => count($data['choices']),
            ]);

            // Apaga todas as choices antigas relacionadas
            $selection->choices()->delete();

            // Cria as novas choices
            $selection->choices()->createMany($data['choices']);

            return response()->json([
                'success' => true,
                'message' => 'Selection updated successfully',
                'data' => $selection->load('choices'),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating selection',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            // Busca a seleção
            $selection = Selection::findOrFail($id);

            // Remove todas as choices relacionadas
            $selection->choices()->delete();

            // Remove a seleção
            $selection->delete();

            return response()->json([
                'success' => true,
                'message' => 'Selections deleted successfully'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting Selection',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function search(Request $request)
    {
        try {
            $jobNo = $request->input('job_no');
            $path  = resource_path('json/selection_' . $jobNo . '.json');
            if (!file_exists($path)) {
                return response()->json([
                    'success' => false,
                    'message' => 'File not found',
                ], Response::HTTP_NOT_FOUND);
            }

            $data = json_decode(file_get_contents($path), true);

            // Se for solicitado salvar no banco
            if ($request->boolean('save_selection')) {

                // Validação leve antes de salvar no banco (baseado no método store)
                $validated = Validator::make($data, [
                    'jobNo' => 'required|string',
                    'client' => 'required|string',
                    'houseType' => 'nullable|string',
                    'elevation' => 'nullable|string',
                    'specification' => 'nullable|string',
                    'lotAddress' => 'nullable|string',
                    'choices' => 'required|array|min:1',
                ])->validate();

                // Criação da Selection no banco
                $selection = Selection::create([
                    'jobNo' => $validated['jobNo'],
                    'client' => $validated['client'],
                    'houseType' => $validated['houseType'] ?? null,
                    'elevation' => $validated['elevation'] ?? null,
                    'specification' => $validated['specification'] ?? null,
                    'lotAddress' => $validated['lotAddress'] ?? null,
                    'choices_count' => count($validated['choices']),
                ]);

                $selection->choices()->createMany($validated['choices']);

                // Retorna como resposta a seleção recém-salva
                return response()->json([
                    'success' => true,
                    'message' => 'Dados salvos e retornados com sucesso',
                    'data'    => $selection->load('choices'),
                ], Response::HTTP_OK);
            }

            // Caso não precise salvar, apenas retornar os dados do JSON
            return response()->json([
                'success' => true,
                'message' => 'Data recovered successfully',
                'data'    => $data,
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve data',
                'error'   => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // /**
    //  * Find the file resources/json/selection_{job_no}.json
    //  * and returns its content.
    //  */
    // public function search(Request $request)
    // {
    //     try {
    //         $jobNo = $request->input('job_no');
    //         $path  = resource_path('json/selection_' . $jobNo . '.json');

    //         if (!file_exists($path)) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'File not found',
    //             ], Response::HTTP_NOT_FOUND);
    //         }

    //         $data = json_decode(file_get_contents($path), true);

    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Data recovered successfully',
    //             'data'    => $data,
    //         ], Response::HTTP_OK);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Failed to retrieve data',
    //             'error'   => $e->getMessage(),
    //         ], Response::HTTP_INTERNAL_SERVER_ERROR);
    //     }
    // }
}
