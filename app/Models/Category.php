<?php

namespace App\Models;

use App\Models\Concerns\FlushesInventoryStatistics;
use App\Models\Concerns\Searchable;
use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    /** @use HasFactory<CategoryFactory> */
    use FlushesInventoryStatistics, HasFactory, Searchable;

    protected $fillable = [
        'name',
        'description',
    ];

    /**
     * @var list<string>
     */
    protected array $searchable = ['name', 'description'];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
