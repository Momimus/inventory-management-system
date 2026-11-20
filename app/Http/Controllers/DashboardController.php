<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Product;
use App\Models\StockTransaction;
use App\Services\InventoryStatistics;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request, InventoryStatistics $statistics): View
    {
        return view('dashboard', [
            'summary' => $statistics->summary(),
            'alerts' => Product::query()
                ->active()
                ->lowStock()
                ->with('category')
                ->orderBy('quantity')
                ->orderByDesc('reorder_level')
                ->limit(6)
                ->get(),
            'recentTransactions' => StockTransaction::query()
                ->with(['product', 'user', 'supplier'])
                ->latest()
                ->latest('id')
                ->limit(8)
                ->get(),
            'recentActivity' => $request->user()->can('view-activity-log')
                ? ActivityLog::with('user')->latest()->latest('id')->limit(8)->get()
                : collect(),
        ]);
    }
}
