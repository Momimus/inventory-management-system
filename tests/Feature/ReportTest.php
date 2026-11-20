<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private StockService $stock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
        $this->stock = app(StockService::class);
    }

    /**
     * @return list<list<string>>
     */
    private function csvRows(string $content): array
    {
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);

        return array_map('str_getcsv', array_filter(explode("\n", trim($content))));
    }

    public function test_staff_cannot_open_or_export_any_report(): void
    {
        $staff = User::factory()->create();
        $product = Product::factory()->create();

        foreach ([
            '/reports',
            '/reports/inventory',
            '/reports/inventory?format=csv',
            '/reports/movements/in',
            '/reports/movements/out?format=csv',
            '/reports/movements/adjustment',
            '/reports/low-stock?format=csv',
            "/reports/product-history?product={$product->id}&format=csv",
        ] as $url) {
            $this->actingAs($staff)->get($url)->assertForbidden();
        }
    }

    public function test_reports_link_is_only_shown_to_admins(): void
    {
        $this->actingAs(User::factory()->create())->get('/')->assertDontSee(route('reports.index'));
        $this->actingAs($this->admin)->get('/')->assertSee(route('reports.index'));
    }

    public function test_every_report_page_renders_for_admins(): void
    {
        $product = Product::factory()->create();
        $this->stock->stockIn($product, 5, $this->admin);

        foreach ([
            '/reports', '/reports/inventory', '/reports/movements/in', '/reports/movements/out',
            '/reports/movements/adjustment', '/reports/low-stock', '/reports/product-history',
            "/reports/product-history?product={$product->id}",
        ] as $url) {
            $this->actingAs($this->admin)->get($url)->assertOk();
        }
    }

    public function test_inventory_report_totals_and_filters(): void
    {
        $tools = Category::factory()->create();
        Product::factory()->for($tools)->create(['quantity' => 10, 'purchase_price' => 2.5, 'selling_price' => 4]);
        Product::factory()->for($tools)->create(['quantity' => 4, 'purchase_price' => 10, 'selling_price' => 15]);
        Product::factory()->create(['quantity' => 100, 'purchase_price' => 1, 'selling_price' => 2]);

        $this->actingAs($this->admin)->get("/reports/inventory?category={$tools->id}")
            ->assertOk()
            ->assertViewHas('totals', [
                'products' => 2,
                'units' => 14,
                'value_cost' => 65.0,     // 10*2.5 + 4*10
                'value_retail' => 100.0,  // 10*4 + 4*15
            ]);
    }

    public function test_inventory_report_exports_csv_matching_the_filters(): void
    {
        $tools = Category::factory()->create(['name' => 'Tools']);
        Product::factory()->for($tools)->create(['sku' => 'HAM-1', 'name' => 'Hammer', 'quantity' => 3, 'purchase_price' => 12.5]);
        Product::factory()->create(['sku' => 'OTHER-1']);

        $response = $this->actingAs($this->admin)->get("/reports/inventory?category={$tools->id}&format=csv");

        $response->assertOk()->assertDownload();
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));

        $rows = $this->csvRows($response->streamedContent());
        $this->assertSame('SKU', $rows[0][0]);
        $this->assertCount(2, $rows, 'Heading plus one matching product.');
        $this->assertSame(['HAM-1', 'Hammer', 'Tools'], array_slice($rows[1], 0, 3));
        $this->assertSame('37.5', $rows[1][10]);

        $this->assertDatabaseHas('activity_logs', ['action' => 'report.exported', 'user_id' => $this->admin->id]);
    }

    public function test_movement_reports_separate_types_and_respect_date_range(): void
    {
        $product = Product::factory()->create(['quantity' => 0]);

        $this->travelTo(now()->subDays(20));
        $this->stock->stockIn($product, 100, $this->admin, reference: 'OLD-DELIVERY');
        $this->travelBack();
        $this->stock->stockIn($product, 30, $this->admin, reference: 'NEW-DELIVERY');
        $this->stock->stockOut($product, 45, $this->admin, reference: 'NEW-ORDER');
        $this->stock->adjust($product, 80, $this->admin, 'Count');

        $from = now()->subDays(7)->toDateString();
        $to = now()->toDateString();

        $this->actingAs($this->admin)->get("/reports/movements/in?from={$from}&to={$to}")
            ->assertSee('NEW-DELIVERY')
            ->assertDontSee('OLD-DELIVERY')
            ->assertDontSee('NEW-ORDER')
            ->assertViewHas('totals', ['transactions' => 1, 'units' => 30, 'net' => 30]);

        $this->actingAs($this->admin)->get('/reports/movements/in')
            ->assertViewHas('totals', ['transactions' => 2, 'units' => 130, 'net' => 130]);

        $this->actingAs($this->admin)->get('/reports/movements/out')
            ->assertViewHas('totals', ['transactions' => 1, 'units' => 45, 'net' => -45]);

        $this->actingAs($this->admin)->get('/reports/movements/adjustment')
            ->assertViewHas('totals', ['transactions' => 1, 'units' => 5, 'net' => -5]);
    }

    public function test_movement_report_csv_contains_signed_quantities(): void
    {
        $product = Product::factory()->create(['quantity' => 10, 'sku' => 'OUT-SKU']);
        $this->stock->stockOut($product, 4, $this->admin, reference: 'SO-9');

        $rows = $this->csvRows($this->actingAs($this->admin)->get('/reports/movements/out?format=csv')->streamedContent());

        $this->assertCount(2, $rows);
        $this->assertSame('OUT-SKU', $rows[1][2]);
        $this->assertSame('-4', $rows[1][5]);
        $this->assertSame('6', $rows[1][6]);
        $this->assertSame('SO-9', $rows[1][8]);
    }

    public function test_reports_reject_invalid_filters(): void
    {
        $this->actingAs($this->admin)->get('/reports/movements/in?from=2026-05-10&to=2026-05-01')->assertSessionHasErrors('to');
        $this->actingAs($this->admin)->get('/reports/movements/in?from=not-a-date')->assertSessionHasErrors('from');
        $this->actingAs($this->admin)->get('/reports/inventory?format=xlsx')->assertSessionHasErrors('format');
        $this->actingAs($this->admin)->get('/reports/inventory?category=999')->assertSessionHasErrors('category');
        $this->actingAs($this->admin)->get('/reports/movements/refund')->assertNotFound();
    }

    public function test_low_stock_report_calculates_shortfall_and_reorder_cost(): void
    {
        $supplier = Supplier::factory()->create(['name' => 'Paper Co']);
        Product::factory()->for($supplier)->create(['sku' => 'LOW-1', 'quantity' => 2, 'reorder_level' => 10, 'purchase_price' => 1.5]);
        Product::factory()->create(['sku' => 'FINE-1', 'quantity' => 50, 'reorder_level' => 10]);

        $rows = $this->csvRows($this->actingAs($this->admin)->get('/reports/low-stock?format=csv')->streamedContent());

        $this->assertCount(2, $rows);
        $this->assertSame('LOW-1', $rows[1][0]);
        $this->assertSame('Paper Co', $rows[1][3]);
        $this->assertSame('8', $rows[1][8]);
        $this->assertSame('12', $rows[1][11]);
    }

    public function test_product_history_balances_add_up_for_a_period(): void
    {
        $product = Product::factory()->create(['quantity' => 0]);

        $this->travelTo(now()->subDays(30));
        $this->stock->stockIn($product, 50, $this->admin);
        $this->stock->stockOut($product, 10, $this->admin);
        $this->travelBack();

        $this->travelTo(now()->subDays(3));
        $this->stock->stockIn($product, 25, $this->admin);
        $this->stock->stockOut($product, 30, $this->admin);
        $this->stock->adjust($product, 33, $this->admin, 'Count');
        $this->travelBack();

        $from = now()->subDays(7)->toDateString();

        $this->actingAs($this->admin)->get("/reports/product-history?product={$product->id}&from={$from}")
            ->assertOk()
            ->assertViewHas('summary', [
                'opening' => 40,
                'in' => 25,
                'out' => 30,
                'adjustments' => -2,
                'closing' => 33,
            ]);

        $this->assertSame(33, $product->fresh()->quantity, 'Closing balance matches the real quantity.');

        $rows = $this->csvRows($this->actingAs($this->admin)
            ->get("/reports/product-history?product={$product->id}&from={$from}&format=csv")->streamedContent());
        $this->assertCount(4, $rows);
    }

    public function test_csv_cells_that_look_like_formulas_are_neutralised(): void
    {
        Product::factory()->create(['name' => '=HYPERLINK("http://evil.test","Click")', 'sku' => 'CSV-1']);

        $rows = $this->csvRows($this->actingAs($this->admin)->get('/reports/inventory?format=csv')->streamedContent());

        $this->assertSame('\'=HYPERLINK("http://evil.test","Click")', $rows[1][1]);
    }
}
