<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupplierManagementTest extends TestCase
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

    private function validPayload(array $overrides = []): array
    {
        return [
            'name' => 'Acme Wholesale',
            'contact_person' => 'Jane Doe',
            'email' => 'Orders@Acme.test',
            'phone' => '+1 (555) 123-4567',
            'address' => "12 Market Street\nSpringfield",
            'is_active' => '1',
            ...$overrides,
        ];
    }

    public function test_admin_can_create_a_supplier(): void
    {
        $this->actingAs($this->admin)->post('/suppliers', $this->validPayload())->assertSessionHasNoErrors();

        $supplier = Supplier::where('name', 'Acme Wholesale')->sole();
        $this->assertSame('orders@acme.test', $supplier->email);
        $this->assertTrue($supplier->is_active);
        $this->assertDatabaseHas('activity_logs', ['action' => 'supplier.created', 'subject_id' => $supplier->id]);
    }

    public function test_supplier_input_is_validated(): void
    {
        Supplier::factory()->create(['name' => 'Taken Name']);

        $this->actingAs($this->admin)->post('/suppliers', $this->validPayload([
            'name' => 'Taken Name',
            'email' => 'not-an-email',
            'phone' => 'call me maybe',
        ]))->assertSessionHasErrors(['name', 'email', 'phone']);
    }

    public function test_admin_can_deactivate_a_supplier_by_editing_it(): void
    {
        $supplier = Supplier::factory()->create();

        $this->actingAs($this->admin)
            ->put("/suppliers/{$supplier->id}", $this->validPayload(['name' => $supplier->name, 'is_active' => '0']))
            ->assertRedirect("/suppliers/{$supplier->id}");

        $this->assertFalse($supplier->fresh()->is_active);
    }

    public function test_suppliers_can_be_searched_and_filtered_by_status(): void
    {
        Supplier::factory()->create(['name' => 'Northwind Traders']);
        Supplier::factory()->inactive()->create(['name' => 'Old Vendor Ltd']);

        $this->actingAs($this->staff)->get('/suppliers?search=northwind')
            ->assertOk()->assertSee('Northwind Traders')->assertDontSee('Old Vendor Ltd');

        $this->actingAs($this->staff)->get('/suppliers?status=inactive')
            ->assertOk()->assertSee('Old Vendor Ltd')->assertDontSee('Northwind Traders');
    }

    public function test_supplier_page_lists_associated_products(): void
    {
        $supplier = Supplier::factory()->create();
        Product::factory()->for($supplier)->create(['name' => 'Copy Paper A4']);

        $this->actingAs($this->staff)->get("/suppliers/{$supplier->id}")->assertOk()->assertSee('Copy Paper A4');
    }

    public function test_unused_supplier_can_be_deleted(): void
    {
        $supplier = Supplier::factory()->create();

        $this->actingAs($this->admin)->delete("/suppliers/{$supplier->id}")->assertRedirect('/suppliers');

        $this->assertModelMissing($supplier);
    }

    public function test_supplier_linked_to_products_cannot_be_deleted(): void
    {
        $supplier = Supplier::factory()->create();
        Product::factory()->for($supplier)->create();

        $this->actingAs($this->admin)->delete("/suppliers/{$supplier->id}")->assertSessionHas('error');

        $this->assertModelExists($supplier);
    }

    public function test_staff_can_view_but_not_change_suppliers(): void
    {
        $supplier = Supplier::factory()->create(['name' => 'Original Supplier']);

        $this->actingAs($this->staff)->get('/suppliers')->assertOk();
        $this->actingAs($this->staff)->get("/suppliers/{$supplier->id}")->assertOk();
        $this->actingAs($this->staff)->get('/suppliers/create')->assertForbidden();
        $this->actingAs($this->staff)->post('/suppliers', $this->validPayload())->assertForbidden();
        $this->actingAs($this->staff)->get("/suppliers/{$supplier->id}/edit")->assertForbidden();
        $this->actingAs($this->staff)->put("/suppliers/{$supplier->id}", $this->validPayload())->assertForbidden();
        $this->actingAs($this->staff)->delete("/suppliers/{$supplier->id}")->assertForbidden();

        $this->assertSame('Original Supplier', $supplier->fresh()->name);
    }

    public function test_supplier_address_is_escaped_while_keeping_line_breaks(): void
    {
        $supplier = Supplier::factory()->create(['address' => "<img src=x onerror=alert(1)>\nSecond line"]);

        $this->actingAs($this->staff)->get("/suppliers/{$supplier->id}")
            ->assertOk()
            ->assertDontSee('<img src=x onerror=alert(1)>', escape: false)
            ->assertSee('&lt;img src=x onerror=alert(1)&gt;<br />', escape: false);
    }
}
