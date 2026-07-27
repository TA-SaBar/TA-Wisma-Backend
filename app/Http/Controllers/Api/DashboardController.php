<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    protected DashboardService $dashboardService;

    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    /**
     * Get dashboard statistics based on user role.
     *
     * GET /api/dashboard/stats
     */
    public function stats(Request $request): JsonResponse
    {
        $user = $request->user();
        $stats = $this->dashboardService->getStatsByRole(
            $user, 
            $request->input('start_date'), 
            $request->input('end_date')
        );

        return response()->json([
            'success' => true,
            'data'    => $stats,
            'role'    => $user->role,
        ]);
    }
}
