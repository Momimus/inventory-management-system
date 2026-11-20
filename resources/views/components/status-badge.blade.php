@props(['active', 'on' => 'Active', 'off' => 'Inactive'])

<span @class(['badge', 'text-bg-success' => $active, 'text-bg-secondary' => ! $active])>{{ $active ? $on : $off }}</span>
