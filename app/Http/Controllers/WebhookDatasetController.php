<?php

namespace App\Http\Controllers;

use App\Models\WebhookDataset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class WebhookDatasetController extends Controller
{
    /**
     * List datasets for the authenticated user's company.
     */
    public function index(Request $request)
    {
        try {
            $user = Auth::user();
            if (!$user || !$user->company_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'User must be associated with a company'
                ], 400);
            }

            $perPage = $request->input('per_page', 15);
            
            $datasets = WebhookDataset::where('company_id', $user->company_id)
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $datasets
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to list datasets',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get a specific dataset if it belongs to the user's company.
     */
    public function show($id)
    {
        try {
            $user = Auth::user();
            if (!$user || !$user->company_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'User must be associated with a company'
                ], 400);
            }

            $dataset = WebhookDataset::where('id', $id)
                ->where('company_id', $user->company_id)
                ->first();

            if (!$dataset) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dataset not found or not authorized'
                ], 404);
            }

            // Ler o conteúdo do arquivo
            $content = Storage::get($dataset->path);

            return response()->json([
                'success' => true,
                'data' => [
                    'dataset' => $dataset,
                    'content' => json_decode($content)
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve dataset',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a dataset if it belongs to the user's company.
     */
    public function destroy($id)
    {
        try {
            $user = Auth::user();
            if (!$user || !$user->company_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'User must be associated with a company'
                ], 400);
            }

            $dataset = WebhookDataset::where('id', $id)
                ->where('company_id', $user->company_id)
                ->first();

            if (!$dataset) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dataset not found or not authorized'
                ], 404);
            }

            // Remover o arquivo físico
            Storage::delete($dataset->path);
            
            // Remover o registro do banco
            $dataset->delete();

            return response()->json([
                'success' => true,
                'message' => 'Dataset deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete dataset',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
