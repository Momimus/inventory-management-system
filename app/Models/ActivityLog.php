<?php

namespace App\Models;

use App\Models\Concerns\IsAppendOnly;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ActivityLog extends Model
{
    use IsAppendOnly;

    public const UPDATED_AT = null;

    /**
     * The first part of each action name ("product" in "product.updated"),
     * used to filter the log by area.
     */
    public const AREAS = [
        'auth' => 'Sign-in and sign-out',
        'profile' => 'Profiles',
        'user' => 'User management',
        'category' => 'Categories',
        'product' => 'Products',
        'supplier' => 'Suppliers',
        'stock' => 'Stock movements',
        'report' => 'Report exports',
    ];

    protected $fillable = [
        'user_id',
        'action',
        'subject_type',
        'subject_id',
        'description',
        'properties',
        'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'properties' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeInArea(Builder $query, ?string $area): void
    {
        $query->when($area, fn (Builder $query) => $query->where('action', 'like', "{$area}.%"));
    }

    public function scopeBetweenDates(Builder $query, ?CarbonInterface $from, ?CarbonInterface $to): void
    {
        $query
            ->when($from, fn (Builder $query) => $query->where('created_at', '>=', $from->copy()->startOfDay()))
            ->when($to, fn (Builder $query) => $query->where('created_at', '<=', $to->copy()->endOfDay()));
    }

    /**
     * Link to the record this entry is about, if it still exists and has a page.
     */
    public function subjectUrl(): ?string
    {
        return match (true) {
            $this->subject instanceof Product => route('products.show', $this->subject),
            $this->subject instanceof Category => route('categories.show', $this->subject),
            $this->subject instanceof Supplier => route('suppliers.show', $this->subject),
            $this->subject instanceof StockTransaction => route('stock.show', $this->subject),
            $this->subject instanceof User => route('users.edit', $this->subject),
            default => null,
        };
    }
}
