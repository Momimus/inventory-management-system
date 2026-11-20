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
        return [
            'category_id' => Category::factory(),
            'name' => ucfirst(fake()->unique()->words(3, true)),
            'sku' => strtoupper(fake()->unique()->bothify('???-####')),
            'description' => fake()->optional()->sentence(),
            'unit_price' => fake()->randomFloat(2, 1, 500),
            'quantity' => fake()->numberBetween(20, 200),
            'reorder_level' => 10,
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
}
