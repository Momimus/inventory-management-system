<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_shows_inventory_counts(): void
    {
        $category = Category::create(['name' => 'Stationery']);

        $restocked = $category->products()->create(['name' => 'Pen', 'sku' => 'PEN-01', 'reorder_level' => 5]);
        $restocked->forceFill(['quantity' => 20])->save();

        $category->products()->create(['name' => 'Notebook', 'sku' => 'NB-01', 'reorder_level' => 5]);

        $this->get('/')
            ->assertOk()
            ->assertViewHas('productCount', 2)
            ->assertViewHas('categoryCount', 1)
            ->assertViewHas('lowStockCount', 1);
    }

    public function test_low_stock_scope_includes_products_at_reorder_level(): void
    {
        $category = Category::create(['name' => 'Tools']);
        $product = $category->products()->create(['name' => 'Hammer', 'sku' => 'HM-01', 'reorder_level' => 3]);
        $product->forceFill(['quantity' => 3])->save();

        $this->assertTrue($product->isLowStock());
        $this->assertSame(1, Product::lowStock()->count());
    }
}
