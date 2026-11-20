<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $staff;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
        $this->staff = User::factory()->create();
    }

    public function test_everyone_signed_in_can_view_and_search_categories(): void
    {
        Category::factory()->create(['name' => 'Office Supplies']);
        Category::factory()->create(['name' => 'Cleaning']);

        $this->actingAs($this->staff)->get('/categories?search=office')
            ->assertOk()
            ->assertSee('Office Supplies')
            ->assertDontSee('Cleaning');
    }

    public function test_category_list_shows_product_counts_and_stock(): void
    {
        $category = Category::factory()->create();
        Product::factory()->for($category)->create(['quantity' => 15]);
        Product::factory()->for($category)->create(['quantity' => 25]);

        $this->actingAs($this->staff)->get('/categories')
            ->assertOk()
            ->assertViewHas('categories', fn ($categories) => $categories->first()->products_count === 2
                && (int) $categories->first()->products_sum_quantity === 40);
    }

    public function test_admin_can_create_and_update_a_category(): void
    {
        $this->actingAs($this->admin)
            ->post('/categories', ['name' => '  Electronics  ', 'description' => 'Devices'])
            ->assertRedirect('/categories');

        $category = Category::where('name', 'Electronics')->sole();

        $this->actingAs($this->admin)
            ->put("/categories/{$category->id}", ['name' => 'Electronics & Gadgets', 'description' => null])
            ->assertRedirect("/categories/{$category->id}");

        $this->assertSame('Electronics & Gadgets', $category->fresh()->name);
        $this->assertDatabaseHas('activity_logs', ['action' => 'category.created', 'subject_id' => $category->id]);
        $this->assertDatabaseHas('activity_logs', ['action' => 'category.updated', 'subject_id' => $category->id]);
    }

    public function test_category_name_must_be_unique(): void
    {
        Category::factory()->create(['name' => 'Tools']);

        $this->actingAs($this->admin)->post('/categories', ['name' => 'Tools'])->assertSessionHasErrors('name');
        $this->actingAs($this->admin)->post('/categories', ['name' => ''])->assertSessionHasErrors('name');
    }

    public function test_empty_category_can_be_deleted(): void
    {
        $category = Category::factory()->create();

        $this->actingAs($this->admin)->delete("/categories/{$category->id}")->assertRedirect('/categories');

        $this->assertModelMissing($category);
        $this->assertDatabaseHas('activity_logs', ['action' => 'category.deleted']);
    }

    public function test_category_with_products_cannot_be_deleted_without_reassignment(): void
    {
        $category = Category::factory()->create();
        Product::factory()->for($category)->create();

        $this->actingAs($this->admin)->delete("/categories/{$category->id}")->assertSessionHasErrors('reassign_to');
        $this->actingAs($this->admin)
            ->delete("/categories/{$category->id}", ['reassign_to' => $category->id])
            ->assertSessionHasErrors('reassign_to');

        $this->assertModelExists($category);
    }

    public function test_products_are_moved_when_a_category_is_deleted_with_reassignment(): void
    {
        $category = Category::factory()->create();
        $target = Category::factory()->create();
        $products = Product::factory()->count(3)->for($category)->create();

        $this->actingAs($this->admin)
            ->delete("/categories/{$category->id}", ['reassign_to' => $target->id])
            ->assertRedirect('/categories');

        $this->assertModelMissing($category);
        $this->assertSame(3, $target->products()->count());
        $this->assertTrue($products->every(fn ($product) => $product->fresh()->category_id === $target->id));
    }

    public function test_database_refuses_to_delete_a_category_that_has_products(): void
    {
        $category = Category::factory()->create();
        Product::factory()->for($category)->create();

        $this->expectException(QueryException::class);

        $category->delete();
    }

    public function test_staff_cannot_change_categories(): void
    {
        $category = Category::factory()->create(['name' => 'Original']);

        $this->actingAs($this->staff)->get('/categories/create')->assertForbidden();
        $this->actingAs($this->staff)->post('/categories', ['name' => 'New'])->assertForbidden();
        $this->actingAs($this->staff)->get("/categories/{$category->id}/edit")->assertForbidden();
        $this->actingAs($this->staff)->put("/categories/{$category->id}", ['name' => 'Changed'])->assertForbidden();
        $this->actingAs($this->staff)->delete("/categories/{$category->id}")->assertForbidden();

        $this->assertSame('Original', $category->fresh()->name);
        $this->assertDatabaseMissing('categories', ['name' => 'New']);
    }

    public function test_category_names_are_escaped_in_html(): void
    {
        Category::factory()->create(['name' => '<script>alert("x")</script>']);

        $this->actingAs($this->staff)->get('/categories')
            ->assertOk()
            ->assertDontSee('<script>alert("x")</script>', escape: false)
            ->assertSee('&lt;script&gt;', escape: false);
    }
}
