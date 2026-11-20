<?php

namespace App\Models\Concerns;

use App\Services\InventoryStatistics;
use Illuminate\Support\Facades\DB;

/**
 * Clears the cached dashboard figures whenever the model changes. The cache
 * is cleared after the surrounding database transaction commits, so another
 * request cannot re-cache the old numbers in between.
 */
trait FlushesInventoryStatistics
{
    protected static function bootFlushesInventoryStatistics(): void
    {
        $flush = fn () => DB::afterCommit(fn () => InventoryStatistics::flush());

        static::saved($flush);
        static::deleted($flush);
    }
}
