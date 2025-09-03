<?php

namespace App\Http\Controllers;

use App\Models\Log;
use App\Models\User;
use App\Models\Selection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Display system logs
     *
     * @return JsonResponse
     */
    public function index()
    {
        try {
            $logs = Log::with('user')
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Logs retrieved successfully',
                'data' => $logs
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving logs',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get dashboard statistics
     *
     * @return JsonResponse
     */
    public function stats()
    {
        try {
            $activeUsers = Log::whereDate('created_at', Carbon::today())
                ->where('autenticado', 1)
                ->distinct('user_id')
                ->count('user_id');

            $todayAccess = Log::whereDate('created_at', Carbon::today())->count();

            $uniqueIPs = Log::whereDate('created_at', Carbon::today())
                ->distinct('ip')
                ->count('ip');

            $failedAttempts = Log::whereDate('created_at', Carbon::today())
                ->where('autenticado', 0)
                ->count();

            $accessByHour = Log::whereDate('created_at', Carbon::today())
                ->selectRaw('HOUR(created_at) as hour, COUNT(*) as count')
                ->groupBy('hour')
                ->orderBy('hour')
                ->get();

            $fullAccessByHour = [];
            for ($i = 0; $i < 24; $i++) {
                $hourData = $accessByHour->where('hour', $i)->first();
                $fullAccessByHour[] = [
                    'hour' => $i,
                    'count' => $hourData ? $hourData->count : 0
                ];
            }

            return response()->json([
                'success' => true,
                'message' => 'Dashboard statistics retrieved successfully',
                'data' => [
                    'active_users' => $activeUsers,
                    'today_access' => $todayAccess,
                    'unique_ips' => $uniqueIPs,
                    'failed_attempts' => $failedAttempts,
                    'access_by_hour' => $fullAccessByHour,
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving dashboard statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user statistics
     *
     * @return JsonResponse
     */
    public function userStats()
    {
        try {
            $totalUsers = User::count();

            $authenticatedUsers = Log::where('autenticado', 1)
                ->distinct('user_id')
                ->count('user_id');

            $newUsersToday = User::whereDate('created_at', Carbon::today())->count();

            $newUsersThisMonth = User::whereYear('created_at', Carbon::now()->year)
                ->whereMonth('created_at', Carbon::now()->month)
                ->count();

            return response()->json([
                'success' => true,
                'message' => 'User statistics retrieved successfully',
                'data' => [
                    'total_users' => $totalUsers,
                    'authenticated_users' => $authenticatedUsers,
                    'new_users_today' => $newUsersToday,
                    'new_users_month' => $newUsersThisMonth,
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving user statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get recent logs
     *
     * @param int $limit Number of logs to return
     * @return JsonResponse
     */
    public function recent($limit = 10)
    {
        try {
            $logs = Log::with('user')
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Recent logs retrieved successfully',
                'data' => $logs
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving recent logs',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user activity statistics
     *
     * @return JsonResponse
     */
    public function userActivities()
    {
        try {
            $userActivities = Log::with('user')
                ->select('user_id', DB::raw('COUNT(*) as total_activities'))
                ->groupBy('user_id')
                ->orderBy('total_activities', 'desc')
                ->limit(10)
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'User activities retrieved successfully',
                'data' => $userActivities
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving user activities',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * List users with pagination
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function listUsers(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 10);
            $page = $request->input('page', 1);

            $users = User::orderBy('created_at', 'desc')
                ->paginate($perPage, ['*'], 'page', $page);

            return response()->json([
                'success' => true,
                'message' => 'Users listed successfully',
                'data' => [
                    'users' => $users->items(),
                    'pagination' => [
                        'total' => $users->total(),
                        'per_page' => $users->perPage(),
                        'current_page' => $users->currentPage(),
                        'last_page' => $users->lastPage(),
                        'from' => $users->firstItem(),
                        'to' => $users->lastItem(),
                    ],
                    'links' => [
                        'first' => $users->url(1),
                        'last' => $users->url($users->lastPage()),
                        'prev' => $users->previousPageUrl(),
                        'next' => $users->nextPageUrl(),
                    ],
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error listing users',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Advanced log filter
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function filter(Request $request)
    {
        try {
            $query = Log::with('user');

            if ($request->filled('user_id')) {
                $query->where('user_id', $request->user_id);
            }

            if ($request->filled('autenticado')) {
                $query->where('autenticado', $request->boolean('autenticado'));
            }

            if ($request->filled('ip')) {
                $query->where('ip', 'like', '%' . $request->ip . '%');
            }

            if ($request->filled('start_date')) {
                $query->whereDate('created_at', '>=', $request->start_date);
            }

            if ($request->filled('end_date')) {
                $query->whereDate('created_at', '<=', $request->end_date);
            }

            if ($request->filled('rota')) {
                $query->where('rota', 'like', '%' . $request->rota . '%');
            }

            $logs = $query->orderBy('created_at', 'desc')->paginate(15);

            return response()->json([
                'success' => true,
                'message' => 'Filtered logs retrieved successfully',
                'data' => [
                    'logs' => $logs->items(),
                    'pagination' => [
                        'total' => $logs->total(),
                        'per_page' => $logs->perPage(),
                        'current_page' => $logs->currentPage(),
                        'last_page' => $logs->lastPage(),
                        'from' => $logs->firstItem(),
                        'to' => $logs->lastItem(),
                    ],
                    'links' => [
                        'first' => $logs->url(1),
                        'last' => $logs->url($logs->lastPage()),
                        'prev' => $logs->previousPageUrl(),
                        'next' => $logs->nextPageUrl(),
                    ],
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error filtering logs',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test endpoint
     *
     * @return JsonResponse
     */
    public function teste()
    {
        $test = Selection::create(['title' => 'This is a test']);

        return response()->json([
            'success' => true,
            'message' => 'Test executed successfully',
            'data' => 'This is a test endpoint'
        ], 200);
    }

    /**
     * CORS Test
     *
     * @return JsonResponse
     */
    public function corsTest()
    {
        return response()->json([
            'success' => true,
            'message' => 'CORS Test - Headers should be present',
            'data' => [
                'timestamp' => now()->toDateTimeString(),
                'origin' => request()->headers->get('Origin'),
                'user_agent' => request()->headers->get('User-Agent'),
            ]
        ]);
    }
}
