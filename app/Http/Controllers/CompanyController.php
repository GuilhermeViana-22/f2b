<?php

namespace App\Http\Controllers;

use App\Http\Requests\Companies\CompaniesUpdateRequest;
use App\Http\Resources\Companies\CompanyResource;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;
use App\Http\Requests\Companies\CompaniesDeleteRequest;
use App\Http\Requests\Companies\CompaniesIndexRequest;
use App\Http\Requests\Companies\CompaniesStoreRequest;
use Illuminate\Http\Response;
use App\Models\Company;

class CompanyController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/companies",
     *     summary="Get list of companies",
     *     description="Returns a paginated list of companies",
     *     tags={"Companies"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="company",
     *         in="query",
     *         description="Filter by company name",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="abn",
     *         in="query",
     *         description="Filter by ABN",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="admin_email",
     *         in="query",
     *         description="Filter by admin email",
     *         required=false,
     *         @OA\Schema(type="string", format="email")
     *     ),
     *     @OA\Parameter(
     *         name="invoice_email",
     *         in="query",
     *         description="Filter by invoice email",
     *         required=false,
     *         @OA\Schema(type="string", format="email")
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by status (0 or 1)",
     *         required=false,
     *         @OA\Schema(type="integer", enum={0,1})
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search across company name, ABN, admin_email, and invoice_email",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number for pagination",
     *         required=false,
     *         @OA\Schema(type="integer", minimum=1)
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Results per page",
     *         required=false,
     *         @OA\Schema(type="integer", minimum=1, maximum=50)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of companies retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Companies retrieved successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="companies", type="array", @OA\Items(ref="#/components/schemas/Companies")),
     *                 @OA\Property(property="pagination", type="object"),
     *                 @OA\Property(property="links", type="object")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function index(CompaniesIndexRequest $request)
    {
        try {
            $query = $this->applyFilters(Company::query(), $request);
            $companies = $this->paginateResults($query, $request);

            return response()->json([
                'success' => true,
                'message' => 'Companies retrieved successfully',
                'data' => [
                    'companies' => CompanyResource::collection($companies->items()),
                    'pagination' => [
                        'total' => $companies->total(),
                        'per_page' => $companies->perPage(),
                        'current_page' => $companies->currentPage(),
                        'last_page' => $companies->lastPage(),
                        'from' => $companies->firstItem(),
                        'to' => $companies->lastItem(),
                    ],
                    'links' => [
                        'first' => $companies->url(1),
                        'last' => $companies->url($companies->lastPage()),
                        'prev' => $companies->previousPageUrl(),
                        'next' => $companies->nextPageUrl(),
                    ],
                ]
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve companies',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/companies",
     *     summary="Create a new company",
     *     tags={"Companies"},
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/CompanyCreateRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Companies created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Companies created successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Companies")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function store(CompaniesStoreRequest $request)
    {
        try {
            $data = $request->validated();

            $company = Company::create($data);

            return response()->json([
                'success' => true,
                'message' => 'Company created successfully',
                'data' => new CompanyResource($company)
            ], ResponseAlias::HTTP_CREATED);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create company',
                'error' => $e->getMessage()
            ], ResponseAlias::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    /**
     * @OA\Get(
     *     path="/api/companies/{id}",
     *     summary="Get a specific company",
     *     tags={"Companies"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Companies ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Companies retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Companies retrieved successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Companies")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Companies not found"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function show($id)
    {
        try {
            $company = Company::find($id);

            if (!$company) {
                return response()->json([
                    'success' => false,
                    'message' => 'Company not found'
                ], ResponseAlias::HTTP_NOT_FOUND);
            }

            return response()->json([
                'success' => true,
                'message' => 'Company retrieved successfully',
                'data' => new CompanyResource($company)
            ], ResponseAlias::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve company',
                'error' => $e->getMessage()
            ], ResponseAlias::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/companies/{id}",
     *     summary="Update an existing company",
     *     tags={"Companies"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Companies ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/CompanyUpdateRequest")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Companies updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Companies updated successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Companies")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Companies not found"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function update(CompaniesUpdateRequest $request, $id)
    {
        try {
            $company = Company::find($id);

            if (!$company) {
                return response()->json([
                    'success' => false,
                    'message' => 'Company not found'
                ], Response::HTTP_NOT_FOUND);
            }

            // Preenche os campos validados
            $company->fill($request->validated());

            // Garante que o status seja atualizado mesmo se não estiver no $fillable
            if ($request->has('status')) {
                $company->status = $request->input('status');
            }

            $company->save();

            return response()->json([
                'success' => true,
                'message' => 'Company updated successfully',
                'data' => new CompanyResource($company)
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update company',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    /**
     * @OA\Delete(
     *     path="/api/companies/{id}",
     *     summary="Delete a company",
     *     tags={"Companies"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Companies ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Companies deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Companies deleted successfully")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Companies not found"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function destroy($id)
    {
        try {
            $company = Company::find($id);

            if (!$company) {
                return response()->json([
                    'success' => false,
                    'message' => 'Company not found'
                ], Response::HTTP_NOT_FOUND);
            }

            $company->delete();

            return response()->json([
                'success' => true,
                'message' => 'Company deleted successfully'
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete company',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @OA\Patch(
     *     path="/api/companies/{id}/toggle-status",
     *     summary="Toggle company status (active/inactive)",
     *     tags={"Companies"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Company ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Company status toggled successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Company status updated successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Companies")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Company not found"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function toggleStatus($id)
    {
        try {
            $company = Company::find($id);

            if (!$company) {
                return response()->json([
                    'success' => false,
                    'message' => 'Company not found'
                ], Response::HTTP_NOT_FOUND);
            }

            // Toggle status: 1 becomes 0, 0 becomes 1
            $company->status = $company->status ? 0 : 1;
            $company->save();

            return response()->json([
                'success' => true,
                'message' => 'Company status updated successfully',
                'data' => new CompanyResource($company)
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update company status',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    private function applyFilters($query, $request)
    {
        // Generic search parameter - searches across multiple fields
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function($q) use ($searchTerm) {
                $q->where('company', 'like', '%' . $searchTerm . '%')
                  ->orWhere('abn', 'like', '%' . $searchTerm . '%')
                  ->orWhere('admin_email', 'like', '%' . $searchTerm . '%')
                  ->orWhere('invoice_email', 'like', '%' . $searchTerm . '%');
            });
        }
        
        // Specific field filters (for backward compatibility)
        if ($request->filled('company')) {
            $query->where('company', 'like', '%' . $request->company . '%');
        }
        if ($request->filled('abn')) {
            $query->where('abn', 'like', '%' . $request->abn . '%');
        }
        if ($request->filled('admin_email')) {
            $query->where('admin_email', 'like', '%' . $request->admin_email . '%');
        }
        if ($request->filled('invoice_email')) {
            $query->where('invoice_email', 'like', '%' . $request->invoice_email . '%');
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        return $query;
    }

    private function paginateResults($query, $request)
    {
        $perPage = $request->input('per_page', 15);
        return $query->paginate($perPage);
    }
}
