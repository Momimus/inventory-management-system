@props(['name', 'label', 'checked' => false, 'help' => null])

{{-- The hidden input sends 0 when the box is unticked, because browsers omit unticked checkboxes. --}}
<div class="mb-3">
    <input type="hidden" name="{{ $name }}" value="0">
    <div class="form-check form-switch">
        <input class="form-check-input" type="checkbox" role="switch" name="{{ $name }}" id="{{ $name }}" value="1"
               @checked(old($name, $checked))>
        <label class="form-check-label" for="{{ $name }}">{{ $label }}</label>
    </div>
    @if ($help)
        <div class="form-text">{{ $help }}</div>
    @endif
</div>
