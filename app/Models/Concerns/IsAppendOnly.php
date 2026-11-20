<?php

namespace App\Models\Concerns;

use LogicException;

/**
 * Records that form an audit trail may be created but never changed or removed.
 * The guard sits on the model so it holds no matter which controller, job or
 * tinker session touches the record.
 */
trait IsAppendOnly
{
    protected static function bootIsAppendOnly(): void
    {
        static::updating(function () {
            throw new LogicException(static::class.' records cannot be modified.');
        });

        static::deleting(function () {
            throw new LogicException(static::class.' records cannot be deleted.');
        });
    }
}
