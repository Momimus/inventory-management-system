<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductImageUploadTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->user = User::factory()->create();
    }

    private function payload(array $overrides = []): array
    {
        return [
            'name' => 'Desk Lamp',
            'sku' => 'LAMP-01',
            'category_id' => Category::factory()->create()->id,
            'purchase_price' => '10.00',
            'selling_price' => '19.99',
            'reorder_level' => '5',
            'is_active' => '1',
            ...$overrides,
        ];
    }

    public function test_valid_image_is_re_encoded_as_webp_with_a_random_name(): void
    {
        $this->actingAs($this->user)->post('/products', $this->payload([
            'image' => UploadedFile::fake()->image('my lamp photo.jpg', 1600, 1200),
        ]))->assertSessionHasNoErrors();

        $path = Product::sole()->image_path;

        $this->assertMatchesRegularExpression('#^products/[A-Za-z0-9]{40}\.webp$#', $path);
        $this->assertStringNotContainsString('lamp', $path);
        Storage::disk('public')->assertExists($path);

        [$width, $height, $type] = getimagesizefromstring(Storage::disk('public')->get($path));
        $this->assertSame(IMAGETYPE_WEBP, $type);
        $this->assertSame(800, $width);
        $this->assertSame(600, $height);
    }

    public function test_images_are_stored_on_the_configured_disk(): void
    {
        // Hosts without a persistent local disk (such as Vercel) set PRODUCT_IMAGE_DISK=s3.
        config(['inventory.product_images.disk' => 's3']);
        Storage::fake('s3');

        $this->actingAs($this->user)->post('/products', $this->payload([
            'image' => UploadedFile::fake()->image('lamp.png', 400, 300),
        ]))->assertSessionHasNoErrors();

        $product = Product::sole();
        Storage::disk('s3')->assertExists($product->image_path);
        Storage::disk('public')->assertMissing($product->image_path);
        $this->assertSame(Storage::disk('s3')->url($product->image_path), $product->imageUrl());
    }

    public function test_php_file_is_rejected(): void
    {
        $this->actingAs($this->user)->post('/products', $this->payload([
            'image' => UploadedFile::fake()->createWithContent('shell.php', '<?php system($_GET["c"]); ?>'),
        ]))->assertSessionHasErrors('image');

        $this->assertSame(0, Product::count());
        $this->assertEmpty(Storage::disk('public')->allFiles());
    }

    public function test_php_code_disguised_with_an_image_extension_is_rejected(): void
    {
        $this->actingAs($this->user)->post('/products', $this->payload([
            'image' => UploadedFile::fake()->createWithContent('photo.jpg', '<?php system($_GET["c"]); ?>'),
        ]))->assertSessionHasErrors('image');

        $this->assertEmpty(Storage::disk('public')->allFiles());
    }

    public function test_svg_is_rejected(): void
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>';

        $this->actingAs($this->user)->post('/products', $this->payload([
            'image' => UploadedFile::fake()->createWithContent('logo.svg', $svg),
        ]))->assertSessionHasErrors('image');
    }

    public function test_oversized_file_is_rejected(): void
    {
        $this->actingAs($this->user)->post('/products', $this->payload([
            'image' => UploadedFile::fake()->image('huge.jpg', 1000, 1000)->size(3000),
        ]))->assertSessionHasErrors('image');
    }

    public function test_image_with_excessive_dimensions_is_rejected(): void
    {
        $this->actingAs($this->user)->post('/products', $this->payload([
            'image' => UploadedFile::fake()->image('wide.png', 5000, 100),
        ]))->assertSessionHasErrors('image');
    }

    public function test_replacing_an_image_deletes_the_old_file(): void
    {
        $this->actingAs($this->user)->post('/products', $this->payload([
            'image' => UploadedFile::fake()->image('first.png', 300, 300),
        ]));
        $product = Product::sole();
        $oldPath = $product->image_path;

        $this->actingAs($this->user)->put("/products/{$product->id}", $this->payload([
            'category_id' => $product->category_id,
            'image' => UploadedFile::fake()->image('second.png', 300, 300),
        ]))->assertSessionHasNoErrors();

        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists($product->fresh()->image_path);
    }

    public function test_image_can_be_removed(): void
    {
        $this->actingAs($this->user)->post('/products', $this->payload([
            'image' => UploadedFile::fake()->image('first.png', 300, 300),
        ]));
        $product = Product::sole();
        $path = $product->image_path;

        $this->actingAs($this->user)->put("/products/{$product->id}", $this->payload([
            'category_id' => $product->category_id,
            'remove_image' => '1',
        ]));

        $this->assertNull($product->fresh()->image_path);
        Storage::disk('public')->assertMissing($path);
    }
}
