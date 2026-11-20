@props(['name', 'label', 'value' => null, 'rows' => 3, 'help' => null])

@php
    $id = $attributes->get('id', $name);
    $error = $errors->first($name);
@endphp

<div class="mb-3">
    <label for="{{ $id }}" class="form-label">{{ $label }}</label>
    <textarea name="{{ $name }}" id="{{ $id }}" rows="{{ $rows }}"
              {{ $attributes->except('id')->class(['form-control', 'is-invalid' => $error]) }}>{{ old($name, $value) }}</textarea>
    @if ($help)
        <div class="form-text">{{ $help }}</div>
    @endif
    @if ($error)
        <div class="invalid-feedback">{{ $error }}</div>
    @endif
</div>
