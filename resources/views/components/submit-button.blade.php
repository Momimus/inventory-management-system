@props(['variant' => 'primary'])

{{-- data-loading makes app.js disable the button and show a spinner on submit, which also stops double submissions. --}}
<button type="submit" data-loading {{ $attributes->class(['btn', "btn-{$variant}"]) }}>
    {{ $slot }}
</button>
