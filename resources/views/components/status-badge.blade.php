@props(['active', 'on' => 'Active', 'off' => 'Inactive'])

<span @class(['status-badge', 'status-badge--good' => $active, 'status-badge--neutral' => ! $active])><span class="status-dot" aria-hidden="true"></span>{{ $active ? $on : $off }}</span>
