<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Read-only view of the audit trail. Entries cannot be edited or deleted
 * from the application.
 */
class ActivityLogController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('view-activity-log');

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'area' => ['nullable', Rule::in(array_keys(ActivityLog::AREAS))],
            'user' => ['nullable', 'integer'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', Rule::when($request->filled('from'), 'after_or_equal:from')],
        ]);

        $entries = ActivityLog::query()
            ->with(['user', 'subject'])
            ->inArea($filters['area'] ?? null)
            ->when($filters['user'] ?? null, fn ($query, $id) => $query->where('user_id', $id))
            ->when($filters['search'] ?? null, fn ($query, $term) => $query->whereLike('description', "%{$term}%"))
            ->betweenDates(
                isset($filters['from']) ? Carbon::parse($filters['from']) : null,
                isset($filters['to']) ? Carbon::parse($filters['to']) : null,
            )
            ->latest()
            ->latest('id')
            ->paginate(30)
            ->withQueryString();

        return view('activity.index', [
            'entries' => $entries,
            'filters' => $filters,
            'users' => User::orderBy('name')->pluck('name', 'id'),
        ]);
    }
}
