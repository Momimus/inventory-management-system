<?php

namespace App\Models;

use App\Models\Concerns\Searchable;
use Database\Factories\SupplierFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    /** @use HasFactory<SupplierFactory> */
    use HasFactory, Searchable;

    protected $fillable = [
        'name',
        'contact_person',
        'email',
        'phone',
        'address',
        'is_active',
    ];

    /**
     * @var list<string>
     */
    protected array $searchable = ['name', 'contact_person', 'email', 'phone'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function stockTransactions(): HasMany
    {
        return $this->hasMany(StockTransaction::class);
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * A supplier referenced by products or stock receipts is kept for the
     * record and can only be deactivated.
     */
    public function isInUse(): bool
    {
        return $this->products()->exists() || $this->stockTransactions()->exists();
    }
}
