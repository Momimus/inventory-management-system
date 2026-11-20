@props(['value'])

<span @class(['fw-semibold tabular', 'qty-positive' => $value > 0, 'qty-negative' => $value < 0])>{{ sprintf('%+d', $value) }}</span>
