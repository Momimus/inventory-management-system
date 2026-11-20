{{--
    A single-series horizontal bar list. Bars share one scale, set by the largest value.
    $items: list of ['label' => string, 'value' => number, 'display' => string, 'href' => ?string, 'tip' => ?string]
--}}
@props(['items', 'caption', 'color' => 'var(--ims-series-out)'])

@php
    $items = collect($items);
    $max = max(1, $items->max('value'));
@endphp

<ul class="barlist" aria-label="{{ $caption }}">
    @foreach ($items as $item)
        <li class="barlist-row" @isset($item['tip']) data-chart-tip="{{ $item['label'] }}&#10;{{ $item['tip'] }}" @endisset>
            <span class="barlist-label">
                @if (! empty($item['href']))
                    <a href="{{ $item['href'] }}">{{ $item['label'] }}</a>
                @else
                    {{ $item['label'] }}
                @endif
            </span>
            <span class="barlist-value">{{ $item['display'] }}</span>
            <span class="barlist-track" aria-hidden="true">
                <span class="barlist-fill" style="--bar: {{ $color }}; width: {{ round($item['value'] / $max * 100, 1) }}%"
                      @if ($item['value'] > 0) data-nonzero @endif></span>
            </span>
        </li>
    @endforeach
</ul>
