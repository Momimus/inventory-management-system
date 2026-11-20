<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class ActivityLogger
{
    /**
     * Attribute names that must never be written to the log, at any depth.
     */
    private const REDACTED_KEYS = [
        'password',
        'password_confirmation',
        'current_password',
        'remember_token',
        'token',
    ];

    public function log(
        string $action,
        string $description,
        ?Model $subject = null,
        array $properties = [],
        ?User $user = null,
    ): ActivityLog {
        return ActivityLog::create([
            'user_id' => ($user ?? auth()->user())?->getKey(),
            'action' => $action,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'description' => $description,
            'properties' => $this->redact($properties) ?: null,
            'ip_address' => request()->ip(),
        ]);
    }

    /**
     * Old and new values of the attributes changed by the model's last save.
     */
    public function changesOf(Model $model): array
    {
        $changed = Arr::except($model->getChanges(), ['created_at', 'updated_at']);

        return [
            'old' => Arr::only($model->getPrevious(), array_keys($changed)),
            'new' => $changed,
        ];
    }

    private function redact(array $properties): array
    {
        foreach ($properties as $key => $value) {
            if (in_array($key, self::REDACTED_KEYS, true)) {
                unset($properties[$key]);
            } elseif (is_array($value)) {
                $properties[$key] = $this->redact($value);
            }
        }

        return $properties;
    }
}
