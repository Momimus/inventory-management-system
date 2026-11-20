@props(['name', 'label', 'options' => [], 'value' => null, 'all' => 'All'])

<div class="col-sm-6 col-md-auto">
    <label for="filter-{{ $name }}" class="form-label small text-body-secondary mb-1">{{ $label }}</label>
    <select name="{{ $name }}" id="filter-{{ $name }}" class="form-select">
        <option value="">{{ $all }}</option>
        @foreach ($options as $optionValue => $optionLabel)
            <option value="{{ $optionValue }}" @selected((string) $value === (string) $optionValue)>{{ $optionLabel }}</option>
        @endforeach
    </select>
</div>
