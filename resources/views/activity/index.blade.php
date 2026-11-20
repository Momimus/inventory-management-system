<x-layouts.app title="Activity log">
    <x-page-header title="Activity log" subtitle="Who did what, and when. Entries are permanent and cannot be edited." />

    <x-filter-bar :action="route('activity.index')" :search="$filters['search'] ?? null" placeholder="Description">
        <x-filter-select name="area" label="Area" :options="\App\Models\ActivityLog::AREAS" :value="$filters['area'] ?? null" />
        <x-filter-select name="user" label="User" :options="$users" :value="$filters['user'] ?? null" />
        <div class="col-sm-6 col-md-auto">
            <label for="from" class="form-label small text-body-secondary mb-1">From</label>
            <input type="date" name="from" id="from" value="{{ $filters['from'] ?? '' }}" class="form-control">
        </div>
        <div class="col-sm-6 col-md-auto">
            <label for="to" class="form-label small text-body-secondary mb-1">To</label>
            <input type="date" name="to" id="to" value="{{ $filters['to'] ?? '' }}" class="form-control">
        </div>
    </x-filter-bar>

    <div class="card">
        @if ($entries->isEmpty())
            <x-empty-state icon="activity" title="No activity matches these filters" />
        @else
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th scope="col">When</th>
                            <th scope="col">User</th>
                            <th scope="col">Action</th>
                            <th scope="col">Description</th>
                            <th scope="col">IP address</th>
                            <th scope="col"><span class="visually-hidden">Details</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($entries as $entry)
                            <tr>
                                <td class="text-nowrap small" title="{{ $entry->created_at->toDateTimeString() }}">
                                    {{ $entry->created_at->format('d M Y H:i') }}
                                </td>
                                <td class="small">{{ $entry->user?->name ?? '—' }}</td>
                                <td><code class="small">{{ $entry->action }}</code></td>
                                <td>
                                    @if ($url = $entry->subjectUrl())
                                        <a href="{{ $url }}">{{ $entry->description }}</a>
                                    @else
                                        {{ $entry->description }}
                                    @endif
                                </td>
                                <td class="small text-body-secondary">{{ $entry->ip_address }}</td>
                                <td class="text-end">
                                    @if ($entry->properties)
                                        <button class="btn btn-sm btn-link" type="button" data-bs-toggle="collapse"
                                                data-bs-target="#details-{{ $entry->id }}" aria-expanded="false"
                                                aria-controls="details-{{ $entry->id }}">Details</button>
                                    @endif
                                </td>
                            </tr>
                            @if ($entry->properties)
                                <tr class="collapse" id="details-{{ $entry->id }}">
                                    <td colspan="6" class="bg-body-tertiary">
                                        <pre class="small mb-0">{{ json_encode($entry->properties, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</pre>
                                    </td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <div class="mt-3">{{ $entries->links() }}</div>
</x-layouts.app>
