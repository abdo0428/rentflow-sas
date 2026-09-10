<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use App\Services\TenantDashboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request, DashboardService $dashboard, TenantDashboardService $tenantDashboard): View
    {
        if ($request->user()->hasRole('tenant')) {
            return view('tenant.dashboard', $tenantDashboard->forUser($request->user()));
        }

        return view('dashboard', $dashboard->forUser($request->user()));
    }
}
