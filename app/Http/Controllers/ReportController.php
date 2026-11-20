<?php

namespace App\Http\Controllers;

use App\Enums\StockTransactionType;
use App\Http\Requests\ReportRequest;
use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use App\Reports\InventoryReport;
use App\Reports\LowStockReport;
use App\Reports\MovementReport;
use App\Reports\ProductHistoryReport;
use App\Reports\Report;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Each action builds a report object and either renders it or streams it as
 * CSV (?format=csv), so both outputs share the same filters and query.
 */
class ReportController extends Controller
{
    public function __construct(private ActivityLogger $activity) {}

    public function index(Request $request): View
    {
        $this->authorize('view-reports');

        return view('reports.index');
    }

    public function inventory(ReportRequest $request): View|Response
    {
        $report = new InventoryReport($request->filters());

        return $this->respond($request, $report, 'reports.inventory', fn () => [
            'products' => $report->query()->paginate(25)->withQueryString(),
            'totals' => $report->totals(),
        ]);
    }

    public function movements(ReportRequest $request, StockTransactionType $type): View|Response
    {
        $report = new MovementReport($request->filters(), $type);

        return $this->respond($request, $report, 'reports.movements', fn () => [
            'transactions' => $report->query()->paginate(25)->withQueryString(),
            'totals' => $report->totals(),
            'products' => Product::orderBy('name')->pluck('name', 'id'),
        ]);
    }

    public function lowStock(ReportRequest $request): View|Response
    {
        $report = new LowStockReport($request->filters());

        return $this->respond($request, $report, 'reports.low-stock', fn () => [
            'products' => $report->query()->paginate(25)->withQueryString(),
        ]);
    }

    public function productHistory(ReportRequest $request): View|Response
    {
        $productId = $request->validated('product');
        $products = Product::orderBy('name')->get(['id', 'name', 'sku'])
            ->mapWithKeys(fn (Product $product) => [$product->id => "{$product->name} ({$product->sku})"]);

        if (! $productId) {
            return view('reports.product-history', ['report' => null, 'products' => $products, 'filters' => []]);
        }

        $report = new ProductHistoryReport($request->filters(), Product::findOrFail($productId));

        return $this->respond($request, $report, 'reports.product-history', fn () => [
            'transactions' => $report->query()->paginate(25)->withQueryString(),
            'summary' => $report->summary(),
            'products' => $products,
        ]);
    }

    /**
     * @param  callable(): array<string, mixed>  $viewData
     */
    private function respond(ReportRequest $request, Report $report, string $view, callable $viewData): View|Response
    {
        if ($request->wantsCsv()) {
            $this->activity->log('report.exported', "Exported {$report->title()} as CSV", properties: [
                'filters' => $request->filters(),
            ]);

            return $report->export();
        }

        return view($view, [
            ...$viewData(),
            'report' => $report,
            'filters' => $request->filters(),
            'categories' => Category::orderBy('name')->pluck('name', 'id'),
            'suppliers' => Supplier::orderBy('name')->pluck('name', 'id'),
        ]);
    }
}
