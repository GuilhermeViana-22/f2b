<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ExternalIntegration;
use App\Http\Resources\ExternalIntegrationResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Carbon;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="External Integrations",
 *     description="API endpoints for managing external integrations"
 * )
 */
class ExternalIntegrationController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/external-integrations",
     *     operationId="getExternalIntegrations",
     *     tags={"External Integrations"},
     *     summary="Get list of external integrations",
     *     description="Returns paginated list of external integrations for the authenticated user's company",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="active",
     *         in="query",
     *         description="Filter by active status",
     *         required=false,
     *         @OA\Schema(type="boolean")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/ExternalIntegration"))
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        $query = ExternalIntegration::forCompany($user->company_id);
        
        if ($request->has('active')) {
            $active = filter_var($request->active, FILTER_VALIDATE_BOOLEAN);
            if ($active) {
                $query->active();
            } else {
                $query->where('is_active', false);
            }
        }
        
        $integrations = $query->latest()->paginate(15);
        
        return response()->json([
            'data' => ExternalIntegrationResource::collection($integrations->items()),
            'meta' => [
                'current_page' => $integrations->currentPage(),
                'last_page' => $integrations->lastPage(),
                'per_page' => $integrations->perPage(),
                'total' => $integrations->total()
            ]
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/external-integrations",
     *     operationId="storeExternalIntegration",
     *     tags={"External Integrations"},
     *     summary="Create a new external integration",
     *     description="Creates a new external integration for the authenticated user's company",
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "base_url", "data_endpoint", "data_format"},
     *             @OA\Property(property="name", type="string", example="My API Integration"),
     *             @OA\Property(property="description", type="string", example="Integration with external API for data sync"),
     *             @OA\Property(property="base_url", type="string", format="uri", example="https://api.example.com"),
     *             @OA\Property(property="auth_token_url", type="string", format="uri", example="https://auth.example.com/token"),
     *             @OA\Property(property="data_endpoint", type="string", example="/api/v1/data"),
     *             @OA\Property(property="auth_config", type="object", example={"client_id": "123", "client_secret": "secret"}),
     *             @OA\Property(property="request_config", type="object", example={"headers": {"Accept": "application/json"}}),
     *             @OA\Property(property="data_format", type="string", enum={"json", "xml"}, example="json"),
     *             @OA\Property(property="is_active", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Integration created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", ref="#/components/schemas/ExternalIntegration"),
     *             @OA\Property(property="message", type="string", example="Integration created successfully")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'base_url' => 'required|url|max:500',
            'data_endpoint' => 'required|string|max:500',
            'auth_type' => 'required|in:none,api_key_header,api_key_query,bearer_token,basic_auth,oauth2,token_login',
            'auth_configuration' => 'nullable|array',
            'oauth_token_url' => 'nullable|url|max:500',
            'oauth_grant_type' => 'nullable|in:client_credentials,authorization_code,password',
            'oauth_token_content_type' => 'nullable|in:application/json,application/x-www-form-urlencoded',
            'request_method' => 'required|in:GET,POST,PUT,PATCH,DELETE',
            'request_content_type' => 'nullable|in:application/json,application/x-www-form-urlencoded,multipart/form-data,text/plain',
            'request_headers' => 'nullable|array',
            'request_parameters' => 'nullable|array',
            'request_body' => 'nullable|string',
            'data_format' => 'required|in:json,xml',
            'is_active' => 'boolean'
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }
        
        $user = Auth::user();
        
        $integration = ExternalIntegration::create(array_merge(
            $validator->validated(),
            [
                'company_id' => $user->company_id,
                'is_active' => $request->get('is_active', true)
            ]
        ));
        
        return response()->json([
            'data' => new ExternalIntegrationResource($integration),
            'message' => 'Integration created successfully'
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/external-integrations/{id}",
     *     operationId="showExternalIntegration",
     *     tags={"External Integrations"},
     *     summary="Get specific external integration",
     *     description="Returns details of a specific external integration",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Integration ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", ref="#/components/schemas/ExternalIntegration")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Integration not found"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function show($id): JsonResponse
    {
        $user = Auth::user();
        
        $integration = ExternalIntegration::forCompany($user->company_id)
            ->findOrFail($id);
        
        return response()->json([
            'data' => new ExternalIntegrationResource($integration)
        ]);
    }

    /**
     * @OA\Put(
     *     path="/api/external-integrations/{id}",
     *     operationId="updateExternalIntegration",
     *     tags={"External Integrations"},
     *     summary="Update external integration",
     *     description="Updates an existing external integration",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Integration ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="Updated API Integration"),
     *             @OA\Property(property="description", type="string", example="Updated description"),
     *             @OA\Property(property="base_url", type="string", format="uri", example="https://api.example.com"),
     *             @OA\Property(property="auth_token_url", type="string", format="uri", example="https://auth.example.com/token"),
     *             @OA\Property(property="data_endpoint", type="string", example="/api/v1/data"),
     *             @OA\Property(property="auth_config", type="object"),
     *             @OA\Property(property="request_config", type="object"),
     *             @OA\Property(property="data_format", type="string", enum={"json", "xml"}),
     *             @OA\Property(property="is_active", type="boolean")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Integration updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", ref="#/components/schemas/ExternalIntegration"),
     *             @OA\Property(property="message", type="string", example="Integration updated successfully")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=404, description="Integration not found"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function update(Request $request, $id): JsonResponse
    {
        $user = Auth::user();
        
        $integration = ExternalIntegration::forCompany($user->company_id)
            ->findOrFail($id);
        
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'base_url' => 'sometimes|required|url|max:500',
            'data_endpoint' => 'sometimes|required|string|max:500',
            'auth_type' => 'sometimes|required|in:none,api_key_header,api_key_query,bearer_token,basic_auth,oauth2,token_login',
            'auth_configuration' => 'nullable|array',
            'oauth_token_url' => 'nullable|url|max:500',
            'oauth_grant_type' => 'nullable|in:client_credentials,authorization_code,password',
            'oauth_token_content_type' => 'nullable|in:application/json,application/x-www-form-urlencoded',
            'request_method' => 'sometimes|required|in:GET,POST,PUT,PATCH,DELETE',
            'request_content_type' => 'nullable|in:application/json,application/x-www-form-urlencoded,multipart/form-data,text/plain',
            'request_headers' => 'nullable|array',
            'request_parameters' => 'nullable|array',
            'request_body' => 'nullable|string',
            'data_format' => 'sometimes|required|in:json,xml',
            'is_active' => 'boolean'
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }
        
        $integration->update($validator->validated());
        
        return response()->json([
            'data' => new ExternalIntegrationResource($integration->fresh()),
            'message' => 'Integration updated successfully'
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/external-integrations/{id}",
     *     operationId="deleteExternalIntegration",
     *     tags={"External Integrations"},
     *     summary="Delete external integration",
     *     description="Deletes an external integration",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Integration ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Integration deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Integration deleted successfully")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Integration not found"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function destroy($id): JsonResponse
    {
        $user = Auth::user();
        
        $integration = ExternalIntegration::forCompany($user->company_id)
            ->findOrFail($id);
        
        $integration->delete();
        
        return response()->json([
            'message' => 'Integration deleted successfully'
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/external-integrations/{id}/test",
     *     operationId="testExternalIntegration",
     *     tags={"External Integrations"},
     *     summary="Test external integration",
     *     description="Tests connection to the external integration",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Integration ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Test successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Connection test successful"),
     *             @OA\Property(property="response_time", type="integer", example=250),
     *             @OA\Property(property="tested_at", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Integration not found"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function test($id): JsonResponse
    {
        $user = Auth::user();
        
        $integration = ExternalIntegration::forCompany($user->company_id)
            ->findOrFail($id);
        
        $startTime = microtime(true);
        
        try {
            // Simple connection test - just check if the base URL is reachable
            $context = stream_context_create([
                'http' => [
                    'method' => 'HEAD',
                    'timeout' => 10,
                    'ignore_errors' => true
                ]
            ]);
            
            $headers = @get_headers($integration->base_url, 1, $context);
            $endTime = microtime(true);
            $responseTime = round(($endTime - $startTime) * 1000); // Convert to milliseconds
            
            if ($headers && strpos($headers[0], '200') !== false) {
                // Update last_used timestamp
                $integration->update(['last_used' => Carbon::now()]);
                
                return response()->json([
                    'success' => true,
                    'message' => 'Connection test successful',
                    'response_time' => $responseTime,
                    'tested_at' => Carbon::now()->toISOString()
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Connection test failed - server not reachable',
                    'response_time' => $responseTime,
                    'tested_at' => Carbon::now()->toISOString()
                ]);
            }
        } catch (\Exception $e) {
            $endTime = microtime(true);
            $responseTime = round(($endTime - $startTime) * 1000);
            
            return response()->json([
                'success' => false,
                'message' => 'Connection test failed: ' . $e->getMessage(),
                'response_time' => $responseTime,
                'tested_at' => Carbon::now()->toISOString()
            ]);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/external-integrations/{id}/toggle-active",
     *     operationId="toggleExternalIntegrationActive",
     *     tags={"External Integrations"},
     *     summary="Toggle active status of external integration",
     *     description="Toggles the active status of an external integration",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Integration ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Status toggled successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", ref="#/components/schemas/ExternalIntegration"),
     *             @OA\Property(property="message", type="string", example="Integration status updated successfully")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Integration not found"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function toggleActive($id): JsonResponse
    {
        $user = Auth::user();
        
        $integration = ExternalIntegration::forCompany($user->company_id)
            ->findOrFail($id);
        
        $integration->update(['is_active' => !$integration->is_active]);
        
        return response()->json([
            'data' => new ExternalIntegrationResource($integration->fresh()),
            'message' => 'Integration status updated successfully'
        ]);
    }

    /**
     * Debug external integration request payload
     * 
     * @OA\Post(
     *     path="/api/external-integrations/{id}/debug",
     *     operationId="debugExternalIntegration",
     *     tags={"External Integrations"},
     *     summary="Debug external integration request",
     *     description="Shows the complete request payload that would be sent to the external API without actually executing it",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Integration ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Request payload debug info",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="debug_info", type="object", description="Complete request payload debug information"),
     *             @OA\Property(property="source", type="string", example="My Integration"),
     *             @OA\Property(property="timestamp", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Integration not found"),
     *     @OA\Response(response=422, description="Integration not properly configured"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function debug($id): JsonResponse
    {
        $user = Auth::user();
        
        $integration = ExternalIntegration::forCompany($user->company_id)
            ->findOrFail($id);
        
        if (!$integration->is_active) {
            return response()->json([
                'success' => false,
                'error' => 'Integration is not active',
                'source' => $integration->name,
                'timestamp' => Carbon::now()->toISOString()
            ], 422);
        }
        
        if (!$integration->isConfigured()) {
            return response()->json([
                'success' => false,
                'error' => 'Integration is not properly configured',
                'source' => $integration->name,
                'timestamp' => Carbon::now()->toISOString()
            ], 422);
        }
        
        try {
            $debugInfo = $this->buildRequestDebugInfo($integration);
            
            return response()->json([
                'success' => true,
                'debug_info' => $debugInfo,
                'source' => $integration->name,
                'timestamp' => Carbon::now()->toISOString()
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Debug failed: ' . $e->getMessage(),
                'source' => $integration->name,
                'timestamp' => Carbon::now()->toISOString()
            ], 500);
        }
    }

    /**
     * Execute external integration to fetch data
     * 
     * @OA\Post(
     *     path="/api/external-integrations/{id}/execute",
     *     operationId="executeExternalIntegration",
     *     tags={"External Integrations"},
     *     summary="Execute external integration",
     *     description="Executes the external integration to fetch data from the configured API endpoint. Saves successful responses and returns cached data on API failures.",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Integration ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Integration executed successfully or fallback data returned",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object", description="Data fetched from external API or cached data"),
     *             @OA\Property(property="format", type="string", enum={"json", "xml"}, example="json"),
     *             @OA\Property(property="source", type="string", example="My Integration"),
     *             @OA\Property(property="execution_time", type="integer", example=250),
     *             @OA\Property(property="data_source", type="string", enum={"live", "cache"}, example="live"),
     *             @OA\Property(property="cache_info", type="object", description="Cache information when using fallback data"),
     *             @OA\Property(property="timestamp", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Integration not found"),
     *     @OA\Response(response=422, description="Integration not properly configured"),
     *     @OA\Response(response=500, description="Error executing integration and no cached data available"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function execute($id): JsonResponse
    {
        $user = Auth::user();
        
        $integration = ExternalIntegration::forCompany($user->company_id)
            ->findOrFail($id);
        
        if (!$integration->is_active) {
            // Try to return cached data if available
            $cachedData = $this->getCachedData($integration);
            if ($cachedData) {
                return response()->json([
                    'success' => true,
                    'data' => $cachedData['data'],
                    'format' => $integration->data_format,
                    'source' => $integration->name,
                    'data_source' => 'cache',
                    'cache_info' => [
                        'cached_at' => $cachedData['cached_at'],
                        'reason' => 'Integration is not active - returning cached data'
                    ],
                    'timestamp' => Carbon::now()->toISOString()
                ]);
            }
            
            return response()->json([
                'success' => false,
                'error' => 'Integration is not active and no cached data available',
                'format' => $integration->data_format,
                'source' => $integration->name,
                'timestamp' => Carbon::now()->toISOString()
            ], 422);
        }
        
        if (!$integration->isConfigured()) {
            // Try to return cached data if available
            $cachedData = $this->getCachedData($integration);
            if ($cachedData) {
                return response()->json([
                    'success' => true,
                    'data' => $cachedData['data'],
                    'format' => $integration->data_format,
                    'source' => $integration->name,
                    'data_source' => 'cache',
                    'cache_info' => [
                        'cached_at' => $cachedData['cached_at'],
                        'reason' => 'Integration not properly configured - returning cached data'
                    ],
                    'timestamp' => Carbon::now()->toISOString()
                ]);
            }
            
            return response()->json([
                'success' => false,
                'error' => 'Integration is not properly configured and no cached data available',
                'format' => $integration->data_format,
                'source' => $integration->name,
                'timestamp' => Carbon::now()->toISOString()
            ], 422);
        }
        
        $startTime = microtime(true);
        
        try {
            $result = $this->executeIntegrationRequest($integration);
            $endTime = microtime(true);
            $executionTime = round(($endTime - $startTime) * 1000); // Convert to milliseconds
            
            if ($result['success']) {
                // Save successful response to cache
                $this->saveCachedData($integration, $result['data']);
                
                // Update last used timestamp
                $integration->markAsUsed();
                
                return response()->json([
                    'success' => true,
                    'data' => $result['data'],
                    'format' => $integration->data_format,
                    'source' => $integration->name,
                    'data_source' => 'live',
                    'execution_time' => $executionTime,
                    'timestamp' => Carbon::now()->toISOString()
                ]);
            } else {
                // API request failed, try to return cached data
                $cachedData = $this->getCachedData($integration);
                if ($cachedData) {
                    return response()->json([
                        'success' => true,
                        'data' => $cachedData['data'],
                        'format' => $integration->data_format,
                        'source' => $integration->name,
                        'data_source' => 'cache',
                        'cache_info' => [
                            'cached_at' => $cachedData['cached_at'],
                            'reason' => 'Live API failed - returning cached data',
                            'last_error' => $result['error']
                        ],
                        'execution_time' => $executionTime,
                        'timestamp' => Carbon::now()->toISOString()
                    ]);
                }
                
                return response()->json([
                    'success' => false,
                    'error' => $result['error'],
                    'format' => $integration->data_format,
                    'source' => $integration->name,
                    'execution_time' => $executionTime,
                    'timestamp' => Carbon::now()->toISOString()
                ], 500);
            }
        } catch (\Exception $e) {
            $endTime = microtime(true);
            $executionTime = round(($endTime - $startTime) * 1000);
            
            // Exception occurred, try to return cached data
            $cachedData = $this->getCachedData($integration);
            if ($cachedData) {
                return response()->json([
                    'success' => true,
                    'data' => $cachedData['data'],
                    'format' => $integration->data_format,
                    'source' => $integration->name,
                    'data_source' => 'cache',
                    'cache_info' => [
                        'cached_at' => $cachedData['cached_at'],
                        'reason' => 'Exception occurred - returning cached data',
                        'last_error' => 'Integration execution failed: ' . $e->getMessage()
                    ],
                    'execution_time' => $executionTime,
                    'timestamp' => Carbon::now()->toISOString()
                ]);
            }
            
            return response()->json([
                'success' => false,
                'error' => 'Integration execution failed: ' . $e->getMessage(),
                'format' => $integration->data_format,
                'source' => $integration->name,
                'execution_time' => $executionTime,
                'timestamp' => Carbon::now()->toISOString()
            ], 500);
        }
    }
    
    /**
     * Execute the integration request to external API
     * 
     * @param ExternalIntegration $integration
     * @return array
     */
    private function executeIntegrationRequest(ExternalIntegration $integration): array
    {
        try {
            // Get authentication token if needed
            $authToken = null;
            if (in_array($integration->auth_type, ['oauth2', 'token_login'])) {
                $authResult = $this->getAuthenticationToken($integration);
                if (!$authResult['success']) {
                    return $authResult;
                }
                $authToken = $authResult['token'];
            }
            
            // Prepare request headers
            $headers = [
                'Accept: ' . ($integration->data_format === 'json' ? 'application/json' : 'application/xml'),
            ];
            
            // Add configured headers
            if ($integration->request_headers && is_array($integration->request_headers)) {
                foreach ($integration->request_headers as $key => $value) {
                    $headers[] = $key . ': ' . $value;
                }
            }
            
            // Handle authentication headers
            switch ($integration->auth_type) {
                case 'bearer_token':
                    $token = $integration->auth_configuration['token'] ?? '';
                    if ($token) {
                        $headers[] = 'Authorization: Bearer ' . $token;
                    }
                    break;
                    
                case 'basic_auth':
                    $username = $integration->auth_configuration['username'] ?? '';
                    $password = $integration->auth_configuration['password'] ?? '';
                    if ($username && $password) {
                        $headers[] = 'Authorization: Basic ' . base64_encode($username . ':' . $password);
                    }
                    break;
                    
                case 'api_key_header':
                    $headerName = $integration->auth_configuration['headerName'] ?? 'X-API-Key';
                    $apiKey = $integration->auth_configuration['apiKey'] ?? '';
                    if ($apiKey) {
                        $headers[] = $headerName . ': ' . $apiKey;
                    }
                    break;
                    
                case 'token_login':
                case 'oauth2':
                    if ($authToken) {
                        $headers[] = 'Authorization: Bearer ' . $authToken;
                    }
                    break;
            }
            
            // Build URL with parameters
            $url = rtrim($integration->base_url, '/') . '/' . ltrim($integration->data_endpoint, '/');
            $urlParams = [];
            
            // Add configured query parameters
            if ($integration->request_parameters && is_array($integration->request_parameters)) {
                $urlParams = array_merge($urlParams, $integration->request_parameters);
            }
            
            // Add API key as query parameter if needed
            if ($integration->auth_type === 'api_key_query') {
                $paramName = $integration->auth_configuration['paramName'] ?? 'api_key';
                $apiKey = $integration->auth_configuration['apiKey'] ?? '';
                if ($apiKey) {
                    $urlParams[$paramName] = $apiKey;
                }
            }
            
            if (!empty($urlParams)) {
                $url .= (strpos($url, '?') !== false ? '&' : '?') . http_build_query($urlParams);
            }
            
            // Initialize cURL
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => 0,
                CURLOPT_CUSTOMREQUEST => $integration->request_method,
            ]);
            
            // Add request body if POST and body is configured
            if ($integration->request_method === 'POST' && $integration->request_body) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $integration->request_body);
                $headers[] = 'Content-Type: ' . ($integration->request_content_type ?? 'application/json');
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            }
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            if ($error) {
                return [
                    'success' => false,
                    'error' => 'cURL error: ' . $error
                ];
            }
            
            if ($httpCode >= 400) {
                return [
                    'success' => false,
                    'error' => 'HTTP error ' . $httpCode . ': ' . $response
                ];
            }
            
            // Parse response based on format
            if ($integration->data_format === 'json') {
                $data = json_decode($response, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    return [
                        'success' => false,
                        'error' => 'Invalid JSON response: ' . json_last_error_msg()
                    ];
                }
            } else {
                // For XML, return as string - frontend can parse if needed
                $data = $response;
                
                // Validate XML
                $prevUseErrors = libxml_use_internal_errors(true);
                $xml = simplexml_load_string($response);
                if ($xml === false) {
                    libxml_use_internal_errors($prevUseErrors);
                    return [
                        'success' => false,
                        'error' => 'Invalid XML response'
                    ];
                }
                libxml_use_internal_errors($prevUseErrors);
            }
            
            return [
                'success' => true,
                'data' => $data
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Request execution failed: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Get authentication token for OAuth2 or token login
     * 
     * @param ExternalIntegration $integration
     * @return array
     */
    private function getAuthenticationToken(ExternalIntegration $integration): array
    {
        try {
            if ($integration->auth_type === 'token_login') {
                return $this->getTokenFromLogin($integration);
            } else if ($integration->auth_type === 'oauth2') {
                return $this->getOAuth2Token($integration);
            }
            
            return [
                'success' => false,
                'error' => 'Unknown authentication type'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Authentication failed: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Get token from login endpoint (token_login type)
     * 
     * @param ExternalIntegration $integration
     * @return array
     */
    private function getTokenFromLogin(ExternalIntegration $integration): array
    {
        if (!$integration->oauth_token_url || !$integration->auth_configuration) {
            return [
                'success' => false,
                'error' => 'Login configuration is incomplete'
            ];
        }
        
        $email = $integration->auth_configuration['email'] ?? '';
        $password = $integration->auth_configuration['password'] ?? '';
        $contentType = $integration->oauth_token_content_type ?: 'application/json';
        $tokenPath = $integration->auth_configuration['tokenPath'] ?? 'token';
        
        if (!$email || !$password) {
            return [
                'success' => false,
                'error' => 'Email and password are required for token login'
            ];
        }
        
        // Prepare request data
        if ($contentType === 'application/json') {
            $requestData = json_encode(['email' => $email, 'password' => $password]);
            $headers = ['Content-Type: application/json'];
        } else {
            $requestData = http_build_query(['email' => $email, 'password' => $password]);
            $headers = ['Content-Type: application/x-www-form-urlencoded'];
        }
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $integration->oauth_token_url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $requestData,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            return [
                'success' => false,
                'error' => 'Login request failed: ' . $error
            ];
        }
        
        if ($httpCode >= 400) {
            return [
                'success' => false,
                'error' => 'Login failed with HTTP ' . $httpCode . ': ' . $response
            ];
        }
        
        $responseData = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'success' => false,
                'error' => 'Invalid JSON response from login endpoint'
            ];
        }
        
        // Extract token using path (e.g., "token" or "data.access_token")
        $token = $responseData;
        foreach (explode('.', $tokenPath) as $key) {
            if (is_array($token) && isset($token[$key])) {
                $token = $token[$key];
            } else {
                return [
                    'success' => false,
                    'error' => 'Token not found at path: ' . $tokenPath
                ];
            }
        }
        
        if (!$token || !is_string($token)) {
            return [
                'success' => false,
                'error' => 'No valid token received from login endpoint'
            ];
        }
        
        return [
            'success' => true,
            'token' => $token
        ];
    }
    
    /**
     * Get OAuth2 token - EXACTLY like the working old software
     * 
     * @param ExternalIntegration $integration
     * @return array
     */
    private function getOAuth2Token(ExternalIntegration $integration): array
    {
        if (!$integration->oauth_token_url || !$integration->auth_configuration) {
            return [
                'success' => false,
                'error' => 'OAuth2 configuration is incomplete'
            ];
        }
        
        // Build the auth request data EXACTLY like the old software
        // Map frontend camelCase to OAuth2 standard snake_case
        $postData = [
            'grant_type' => $integration->oauth_grant_type ?: 'client_credentials',
            'client_id' => $integration->auth_configuration['clientId'] ?? $integration->auth_configuration['client_id'] ?? '',
            'client_secret' => $integration->auth_configuration['clientSecret'] ?? $integration->auth_configuration['client_secret'] ?? '',
        ];
        
        // Add username and password for OAuth2 password grant type EXACTLY like old software
        if ($integration->oauth_grant_type === 'password') {
            if (isset($integration->auth_configuration['username'])) {
                $postData['username'] = $integration->auth_configuration['username'];
            }
            if (isset($integration->auth_configuration['password'])) {
                $postData['password'] = $integration->auth_configuration['password'];
            }
        }
        
        // Add scope if provided
        if (isset($integration->auth_configuration['scope'])) {
            $postData['scope'] = $integration->auth_configuration['scope'];
        }
        
        // Add any additional auth parameters
        if (isset($integration->auth_configuration['additionalParams']) && is_array($integration->auth_configuration['additionalParams'])) {
            $postData = array_merge($postData, $integration->auth_configuration['additionalParams']);
        }
        
        // Use the same cURL setup as the old software
        $ch = curl_init($integration->oauth_token_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        if (!$response) {
            curl_close($ch);
            return [
                'success' => false,
                'error' => 'Failed to retrieve access token: ' . $error
            ];
        }
        
        curl_close($ch);
        
        if ($httpCode >= 400) {
            return [
                'success' => false,
                'error' => 'OAuth2 authentication failed with HTTP ' . $httpCode . ': ' . $response
            ];
        }
        
        $data = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'success' => false,
                'error' => 'Invalid JSON response from OAuth2 endpoint'
            ];
        }
        
        if (!isset($data['access_token'])) {
            return [
                'success' => false,
                'error' => 'Authentication failed, no access token returned.'
            ];
        }
        
        return [
            'success' => true,
            'token' => $data['access_token']
        ];
    }

    /**
     * Build complete request debug information
     * 
     * @param ExternalIntegration $integration
     * @return array
     */
    private function buildRequestDebugInfo(ExternalIntegration $integration): array
    {
        $debugInfo = [
            'integration' => [
                'id' => $integration->id,
                'name' => $integration->name,
                'auth_type' => $integration->auth_type,
                'request_method' => $integration->request_method,
                'data_format' => $integration->data_format,
                'is_active' => $integration->is_active
            ],
            'authentication' => [],
            'request' => [
                'method' => $integration->request_method,
                'url' => '',
                'headers' => [],
                'query_parameters' => [],
                'body' => null
            ],
            'oauth_request' => null
        ];

        // Get authentication token if needed
        $authToken = null;
        if (in_array($integration->auth_type, ['oauth2', 'token_login'])) {
            try {
                if ($integration->auth_type === 'token_login') {
                    $debugInfo['oauth_request'] = $this->buildTokenLoginDebugInfo($integration);
                } else if ($integration->auth_type === 'oauth2') {
                    $debugInfo['oauth_request'] = $this->buildOAuth2DebugInfo($integration);
                }
            } catch (\Exception $e) {
                $debugInfo['authentication']['error'] = 'Authentication debug failed: ' . $e->getMessage();
            }
        }

        // Build authentication info
        $debugInfo['authentication'] = [
            'type' => $integration->auth_type,
            'configuration' => $integration->auth_configuration
        ];

        // Prepare request headers
        $headers = [
            'Accept' => ($integration->data_format === 'json' ? 'application/json' : 'application/xml'),
        ];

        // Add configured headers
        if ($integration->request_headers && is_array($integration->request_headers)) {
            foreach ($integration->request_headers as $key => $value) {
                $headers[$key] = $value;
            }
        }

        // Handle authentication headers
        switch ($integration->auth_type) {
            case 'bearer_token':
                $token = $integration->auth_configuration['token'] ?? '';
                if ($token) {
                    $headers['Authorization'] = 'Bearer ' . substr($token, 0, 10) . '...' . substr($token, -5);
                }
                break;
                
            case 'basic_auth':
                $username = $integration->auth_configuration['username'] ?? '';
                $password = $integration->auth_configuration['password'] ?? '';
                if ($username && $password) {
                    $headers['Authorization'] = 'Basic ' . base64_encode($username . ':' . str_repeat('*', strlen($password)));
                }
                break;
                
            case 'api_key_header':
                $headerName = $integration->auth_configuration['headerName'] ?? 'X-API-Key';
                $apiKey = $integration->auth_configuration['apiKey'] ?? '';
                if ($apiKey) {
                    $headers[$headerName] = substr($apiKey, 0, 10) . '...' . substr($apiKey, -5);
                }
                break;
                
            case 'token_login':
            case 'oauth2':
                $headers['Authorization'] = 'Bearer [TOKEN_WILL_BE_OBTAINED_FROM_OAUTH]';
                break;
        }

        // Build URL with parameters
        $url = rtrim($integration->base_url, '/') . '/' . ltrim($integration->data_endpoint, '/');
        $urlParams = [];

        // Add configured query parameters
        if ($integration->request_parameters && is_array($integration->request_parameters)) {
            $urlParams = array_merge($urlParams, $integration->request_parameters);
        }

        // Add API key as query parameter if needed
        if ($integration->auth_type === 'api_key_query') {
            $paramName = $integration->auth_configuration['paramName'] ?? 'api_key';
            $apiKey = $integration->auth_configuration['apiKey'] ?? '';
            if ($apiKey) {
                $urlParams[$paramName] = substr($apiKey, 0, 10) . '...' . substr($apiKey, -5);
            }
        }

        if (!empty($urlParams)) {
            $url .= (strpos($url, '?') !== false ? '&' : '?') . http_build_query($urlParams);
        }

        $debugInfo['request']['url'] = $url;
        $debugInfo['request']['headers'] = $headers;
        $debugInfo['request']['query_parameters'] = $urlParams;

        // Add request body if POST and body is configured
        if ($integration->request_method === 'POST' && $integration->request_body) {
            $debugInfo['request']['body'] = $integration->request_body;
            $debugInfo['request']['headers']['Content-Type'] = $integration->request_content_type ?? 'application/json';
        }

        return $debugInfo;
    }

    /**
     * Build OAuth2 debug information - EXACT payload that would be sent
     */
    private function buildOAuth2DebugInfo(ExternalIntegration $integration): array
    {
        // Build the EXACT same auth request data as the real method
        $authParams = [
            'client_id' => $integration->auth_configuration['client_id'] ?? $integration->auth_configuration['clientId'] ?? '',
            'client_secret' => $integration->auth_configuration['client_secret'] ?? $integration->auth_configuration['clientSecret'] ?? '',
            'grant_type' => $integration->oauth_grant_type ?: 'client_credentials'
        ];

        // Add scope if provided
        if (isset($integration->auth_configuration['scope'])) {
            $authParams['scope'] = $integration->auth_configuration['scope'];
        }

        // Add username and password for OAuth2 password grant type
        if ($integration->oauth_grant_type === 'password') {
            if (isset($integration->auth_configuration['username'])) {
                $authParams['username'] = $integration->auth_configuration['username'];
            }
            if (isset($integration->auth_configuration['password'])) {
                $authParams['password'] = $integration->auth_configuration['password'];
            }
        }

        // Add any additional auth parameters
        if (isset($integration->auth_configuration['additionalParams']) && is_array($integration->auth_configuration['additionalParams'])) {
            $authParams = array_merge($authParams, $integration->auth_configuration['additionalParams']);
        }

        $contentType = $integration->oauth_token_content_type ?: 'application/x-www-form-urlencoded';
        
        // Prepare request data EXACTLY as the real method does
        if ($contentType === 'application/json') {
            $requestData = json_encode($authParams);
            $headers = ['Content-Type: application/json'];
        } else {
            $requestData = http_build_query($authParams);
            $headers = ['Content-Type: application/x-www-form-urlencoded'];
        }

        // Add configured headers EXACTLY as the real method
        if ($integration->request_headers && is_array($integration->request_headers)) {
            foreach ($integration->request_headers as $key => $value) {
                $headers[] = $key . ': ' . $value;
            }
        }
        
        return [
            'url' => $integration->oauth_token_url,
            'method' => 'POST',
            'content_type' => $contentType,
            'raw_parameters' => $authParams,
            'encoded_body' => $requestData,
            'curl_headers' => $headers,
            'configuration_received_from_frontend' => [
                'raw_auth_configuration' => $integration->auth_configuration,
                'client_id_field_found' => $integration->auth_configuration['client_id'] ?? null,
                'clientId_field_found' => $integration->auth_configuration['clientId'] ?? null,
                'client_secret_field_found' => $integration->auth_configuration['client_secret'] ?? null,
                'clientSecret_field_found' => $integration->auth_configuration['clientSecret'] ?? null,
            ]
        ];
    }

    /**
     * Build Token Login debug information
     */
    private function buildTokenLoginDebugInfo(ExternalIntegration $integration): array
    {
        $contentType = $integration->oauth_token_content_type ?: 'application/json';
        
        return [
            'url' => $integration->oauth_token_url,
            'method' => 'POST',
            'content_type' => $contentType,
            'parameters' => [
                'email' => $integration->auth_configuration['email'] ?? '',
                'password' => '[HIDDEN]'
            ],
            'token_path' => $integration->auth_configuration['tokenPath'] ?? 'token',
            'headers' => ['Content-Type' => $contentType]
        ];
    }

    /**
     * Test endpoint that returns a sample JSON file for integration testing
     * 
     * @OA\Get(
     *     path="/api/external-integrations/test-json",
     *     operationId="testIntegrationJson",
     *     tags={"External Integrations"},
     *     summary="Get test JSON data",
     *     description="Returns sample JSON data for testing integration functionality",
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Sample JSON data retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object", description="Sample JSON data from file"),
     *             @OA\Property(property="source", type="string", example="resources/json/selection_24604b.json"),
     *             @OA\Property(property="timestamp", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Test file not found"),
     *     @OA\Response(response=500, description="Error reading test file"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function testIntegrationJson(Request $request): JsonResponse
    {
        try {
            // Path to the JSON test file
            $filePath = resource_path('json/selection_24604b.json');
            
            // Check if file exists
            if (!file_exists($filePath)) {
                return response()->json([
                    'success' => false,
                    'error' => 'Test JSON file not found',
                    'path' => $filePath,
                    'timestamp' => Carbon::now()->toISOString()
                ], 404);
            }
            
            // Read and decode JSON file
            $jsonContent = file_get_contents($filePath);
            $decodedData = json_decode($jsonContent, true);
            
            // Check for JSON decode errors
            if (json_last_error() !== JSON_ERROR_NONE) {
                return response()->json([
                    'success' => false,
                    'error' => 'Invalid JSON format in test file: ' . json_last_error_msg(),
                    'timestamp' => Carbon::now()->toISOString()
                ], 500);
            }
            
            return response()->json([
                'success' => true,
                'data' => $decodedData,
                'source' => 'resources/json/selection_24604b.json',
                'file_size' => filesize($filePath),
                'records_count' => isset($decodedData['choices']) ? count($decodedData['choices']) : 0,
                'timestamp' => Carbon::now()->toISOString()
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Error reading test JSON file: ' . $e->getMessage(),
                'timestamp' => Carbon::now()->toISOString()
            ], 500);
        }
    }

    /**
     * Save successful response data to cache
     * 
     * @param ExternalIntegration $integration
     * @param mixed $data
     * @return bool
     */
    private function saveCachedData(ExternalIntegration $integration, $data): bool
    {
        try {
            $cacheData = [
                'data' => $data,
                'cached_at' => Carbon::now()->toISOString(),
                'integration_id' => $integration->id,
                'integration_name' => $integration->name,
                'company_id' => $integration->company_id,
                'data_format' => $integration->data_format,
                'cache_version' => '1.0'
            ];
            
            // Create cache filename based on integration and company
            $filename = 'integration_cache_' . $integration->company_id . '_' . $integration->id . '.json';
            $cachePath = 'external_integrations/' . $filename;
            
            // Save to storage (local disk)
            $success = Storage::disk('local')->put($cachePath, json_encode($cacheData, JSON_PRETTY_PRINT));
            
            if ($success) {
                // Also log successful cache save
                \Log::info('External integration cache saved', [
                    'integration_id' => $integration->id,
                    'company_id' => $integration->company_id,
                    'cache_file' => $cachePath,
                    'data_size' => strlen(json_encode($data))
                ]);
            }
            
            return $success;
        } catch (\Exception $e) {
            \Log::error('Failed to save integration cache', [
                'integration_id' => $integration->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Get cached data for an integration
     * 
     * @param ExternalIntegration $integration
     * @return array|null
     */
    private function getCachedData(ExternalIntegration $integration): ?array
    {
        try {
            // Create cache filename based on integration and company
            $filename = 'integration_cache_' . $integration->company_id . '_' . $integration->id . '.json';
            $cachePath = 'external_integrations/' . $filename;
            
            // Check if cache file exists
            if (!Storage::disk('local')->exists($cachePath)) {
                return null;
            }
            
            // Read cache file
            $cacheContent = Storage::disk('local')->get($cachePath);
            $cacheData = json_decode($cacheContent, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                \Log::warning('Invalid JSON in cache file', [
                    'integration_id' => $integration->id,
                    'cache_file' => $cachePath,
                    'json_error' => json_last_error_msg()
                ]);
                return null;
            }
            
            // Validate cache data structure
            if (!isset($cacheData['data']) || !isset($cacheData['cached_at'])) {
                \Log::warning('Invalid cache data structure', [
                    'integration_id' => $integration->id,
                    'cache_file' => $cachePath
                ]);
                return null;
            }
            
            // Check if cache is not too old (optional: 24 hours max age)
            $cacheAge = Carbon::now()->diffInHours(Carbon::parse($cacheData['cached_at']));
            if ($cacheAge > 24) {
                \Log::info('Cache data is older than 24 hours, but still returning it', [
                    'integration_id' => $integration->id,
                    'cache_age_hours' => $cacheAge
                ]);
                // Still return old cache data as fallback
            }
            
            return [
                'data' => $cacheData['data'],
                'cached_at' => $cacheData['cached_at'],
                'cache_age_hours' => $cacheAge
            ];
        } catch (\Exception $e) {
            \Log::error('Failed to read integration cache', [
                'integration_id' => $integration->id,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
    
    /**
     * Clear cached data for an integration
     * 
     * @param ExternalIntegration $integration
     * @return bool
     */
    private function clearCachedData(ExternalIntegration $integration): bool
    {
        try {
            $filename = 'integration_cache_' . $integration->company_id . '_' . $integration->id . '.json';
            $cachePath = 'external_integrations/' . $filename;
            
            if (Storage::disk('local')->exists($cachePath)) {
                return Storage::disk('local')->delete($cachePath);
            }
            
            return true; // File doesn't exist, consider it "cleared"
        } catch (\Exception $e) {
            \Log::error('Failed to clear integration cache', [
                'integration_id' => $integration->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}
