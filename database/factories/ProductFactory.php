<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        $purchasePrice = fake()->randomFloat(2, 1, 400);

        return [
            'category_id' => Category::factory(),
            'supplier_id' => null,
            'name' => ucfirst(fake()->unique()->words(3, true)),
            'sku' => strtoupper(fake()->unique()->bothify('???-####')),
            'description' => fake()->optional()->sentence(),
            'purchase_price' => $purchasePrice,
            'selling_price' => round($purchasePrice * fake()->randomFloat(2, 1.15, 1.6), 2),
            'quantity' => fake()->numberBetween(20, 200),
            'reorder_level' => 10,
            'is_active' => true,
        ];
    }

    public function lowStock(): static
    {
        return $this->state(fn () => ['quantity' => 3, 'reorder_level' => 10]);
    }

    public function outOfStock(): static
    {
        return $this->state(fn () => ['quantity' => 0]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
