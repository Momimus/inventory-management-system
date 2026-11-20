<?php

namespace App\Models;

use App\Enums\StockLevel;
use App\Models\Concerns\Searchable;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory, Searchable;

    /**
     * quantity is not fillable: stock only changes through StockService, which
     * records a transaction for every change. image_path is set by the upload code.
     */
    protected $fillable = [
        'category_id',
        'supplier_id',
        'name',
        'sku',
        'description',
        'purchase_price',
        'selling_price',
        'reorder_level',
        'is_active',
    ];

    protected $attributes = [
        'quantity' => 0,
        'reorder_level' => 0,
        'is_active' => true,
    ];

    /**
     * @var list<string>
     */
    protected array $searchable = ['name', 'sku'];

    protected function casts(): array
    {
        return [
            'purchase_price' => 'decimal:2',
            'selling_price' => 'decimal:2',
            'quantity' => 'integer',
            'reorder_level' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function stockTransactions(): HasMany
    {
        return $this->hasMany(StockTransaction::class);
    }

    /**
     * At or below the reorder level, including products with no stock at all.
     */
    public function scopeLowStock(Builder $query): void
    {
        $query->whereColumn('quantity', '<=', 'reorder_level');
    }

    public function scopeOutOfStock(Builder $query): void
    {
        $query->where('quantity', 0);
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function scopeAtStockLevel(Builder $query, StockLevel $level): void
    {
        match ($level) {
            StockLevel::Out => $query->outOfStock(),
            StockLevel::Low => $query->lowStock()->where('quantity', '>', 0),
            StockLevel::InStock => $query->whereColumn('quantity', '>', 'reorder_level'),
        };
    }

    public function stockLevel(): StockLevel
    {
        return match (true) {
            $this->quantity === 0 => StockLevel::Out,
            $this->quantity <= $this->reorder_level => StockLevel::Low,
            default => StockLevel::InStock,
        };
    }

    public function isLowStock(): bool
    {
        return $this->quantity <= $this->reorder_level;
    }

    public function imageUrl(): ?string
    {
        return $this->image_path
            ? Storage::disk(config('inventory.product_images.disk'))->url($this->image_path)
            : null;
    }
}
