@props(['value'])

<span @class(['fw-semibold', 'text-success' => $value > 0, 'text-danger' => $value < 0])>{{ sprintf('%+d', $value) }}</span>
