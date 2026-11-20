@props([
    'name',
    'label',
    'options' => [],
    'value' => null,
    'placeholder' => null,
    'help' => null,
])

{{-- $options is a [value => label] array. --}}
@php
    $id = $attributes->get('id', $name);
    $error = $errors->first($name);
    $selected = (string) old($name, $value instanceof \BackedEnum ? $value->value : $value);
@endphp

<div class="mb-3">
    <label for="{{ $id }}" class="form-label">{{ $label }}</label>
    <select name="{{ $name }}" id="{{ $id }}" {{ $attributes->except('id')->class(['form-select', 'is-invalid' => $error]) }}>
        @if ($placeholder !== null)
            <option value="">{{ $placeholder }}</option>
        @endif
        @foreach ($options as $optionValue => $optionLabel)
            <option value="{{ $optionValue }}" @selected($selected === (string) $optionValue)>{{ $optionLabel }}</option>
        @endforeach
    </select>
    @if ($help)
        <div class="form-text">{{ $help }}</div>
    @endif
    @if ($error)
        <div class="invalid-feedback">{{ $error }}</div>
    @endif
</div>
