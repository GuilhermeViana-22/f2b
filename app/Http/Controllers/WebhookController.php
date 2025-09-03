<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Models\CompanyWebhookToken;
use App\Models\WebhookDataset;
use Illuminate\Support\Facades\Auth;

class WebhookController extends Controller
{
    /**
     * Handle quantity interpretation requests and store JSON data.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    /**
     * Generate a new webhook token for the authenticated user's company.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function generateToken(Request $request)
    {
        try {
            $user = Auth::user();
            if (!$user || !$user->company_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'User must be associated with a company to generate webhook tokens'
                ], 400);
            }

            $result = CompanyWebhookToken::generate($user->company);

            return response()->json([
                'success' => true,
                'message' => 'Webhook token generated successfully. Store this token safely as it won\'t be shown again.',
                'data' => [
                    'token' => $result['plain_token'],
                    'created_at' => $result['model']->created_at
                ]
            ], 201);

        } catch (\Exception $e) {
            Log::error('Failed to generate webhook token: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate webhook token',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * List all webhook tokens for the authenticated user's company.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function listTokens(Request $request)
    {
        try {
            $user = Auth::user();
            if (!$user || !$user->company_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'User must be associated with a company to list webhook tokens'
                ], 400);
            }

            $tokens = CompanyWebhookToken::where('company_id', $user->company_id)
                ->select(['id', 'company_id', 'created_at', 'last_used_at', 'revoked'])
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $tokens
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to list webhook tokens: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to list webhook tokens',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Revoke a specific webhook token.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function revokeToken(Request $request, $id)
    {
        try {
            $user = Auth::user();
            if (!$user || !$user->company_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'User must be associated with a company to revoke webhook tokens'
                ], 400);
            }

            $token = CompanyWebhookToken::where('id', $id)
                ->where('company_id', $user->company_id)
                ->where('id', $id)
                ->first();

            if (!$token) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token not found or not authorized'
                ], 404);
            }

            $token->update(['revoked' => true]);

            return response()->json([
                'success' => true,
                'message' => 'Token revoked successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to revoke webhook token: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to revoke webhook token',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function interpretQuantities(Request $request)
    {
        try {
            // Validate JSON data
            if (!$request->isJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid request format. JSON expected.'
                ], 400);
            }

            $data = $request->json()->all();
            if (empty($data)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Empty JSON payload provided'
                ], 400);
            }

            // Ensure storage directory exists
            $storagePath = 'app/datasets';
            if (!Storage::exists($storagePath)) {
                Storage::makeDirectory($storagePath);
            }

            // Generate unique filename with timestamp
            $filename = 'dataset_' . now()->format('YmdHis') . '_' . Str::random(8) . '.json';
            $fullPath = $storagePath . '/' . $filename;

            // Store JSON exactly as received
            $jsonContent = $request->getContent();
            Storage::put($fullPath, $jsonContent);

            // Extract project info from JSON data
            $projectInfo = $data['projectInfo'][0] ?? null;
            $projectNumber = $projectInfo ? ($projectInfo['PROJECTNUMBER'] ?? null) : null;
            $projectStatus = $projectInfo ? ($projectInfo['PROJECTSTATUS'] ?? null) : null;

            // Salvar no banco de dados
            $dataset = WebhookDataset::create([
                'company_id' => $request->get('company_id'),
                'filename' => $filename,
                'path' => $fullPath,
                'project_number' => $projectNumber,
                'status' => $projectStatus
            ]);

            return response()->json([
                'success' => true,
                'file' => $filename,
                'path' => $fullPath,
                'dataset' => $dataset
            ], 201);

        } catch (\Exception $e) {
            Log::error('Failed to process quantities interpretation: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to process request',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}