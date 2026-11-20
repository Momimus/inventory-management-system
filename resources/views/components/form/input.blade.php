@props([
    'name',
    'label',
    'type' => 'text',
    'value' => null,
    'help' => null,
    'bag' => 'default',
])

@php
    $id = $attributes->get('id', $name);
    $error = $errors->getBag($bag)->first($name);
@endphp

<div class="mb-3">
    <label for="{{ $id }}" class="form-label">{{ $label }}</label>
    <input type="{{ $type }}" name="{{ $name }}" id="{{ $id }}"
           @if ($type !== 'password' && $type !== 'file') value="{{ old($name, $value) }}" @endif
           {{ $attributes->except('id')->class(['form-control', 'is-invalid' => $error]) }}
           @if ($help) aria-describedby="{{ $id }}-help" @endif>
    @if ($help)
        <div id="{{ $id }}-help" class="form-text">{{ $help }}</div>
    @endif
    @if ($error)
        <div class="invalid-feedback">{{ $error }}</div>
    @endif
</div>
