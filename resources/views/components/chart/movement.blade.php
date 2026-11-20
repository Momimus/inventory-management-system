{{--
    Units received and issued per day, as paired columns on one shared axis.
    $days: list of ['date' => Y-m-d, 'in' => int, 'out' => int], oldest first.
--}}
@props(['days'])

@php
    $days = collect($days);
    $peak = (int) $days->flatMap(fn ($day) => [$day['in'], $day['out']])->max();

    // Four evenly spaced, whole-number ticks that clear the peak (1, 2, 2.5 or 5 x 10^n).
    $roughStep = max($peak, 1) / 4;
    $magnitude = 10 ** floor(log10($roughStep));
    $step = collect([1, 2, 2.5, 5, 10])->map(fn ($m) => $m * $magnitude)->first(fn ($s) => $s >= $roughStep);
    $step = max(1, (int) ceil($step));
    $axisMax = $step * 4;
    $ticks = range(0, $axisMax, $step);
    $tickLabel = fn (int $value) => $axisMax >= 10000 ? \Illuminate\Support\Number::abbreviate($value) : number_format($value);
    $isEmpty = $peak === 0;
    $asDate = fn ($date) => \Illuminate\Support\Carbon::parse($date);
@endphp

<figure class="colchart" aria-describedby="movement-table-caption">
    <div class="colchart-axis" aria-hidden="true">
        @foreach ($ticks as $tick)
            <span style="bottom: {{ $tick / $axisMax * 100 }}%">{{ $tickLabel($tick) }}</span>
        @endforeach
    </div>

    <div class="colchart-plot">
        @foreach ($ticks as $tick)
            @continue($tick === 0)
            <span class="colchart-gridline" style="bottom: {{ $tick / $axisMax * 100 }}%" aria-hidden="true"></span>
        @endforeach

        @if ($isEmpty)
            <p class="colchart-empty mb-0">No stock was received or issued in this period.</p>
        @else
            <div class="colchart-groups">
                @foreach ($days as $day)
                    @php($date = $asDate($day['date']))
                    <div class="colchart-group" tabindex="0"
                         data-chart-tip="{{ $date->format('l j M') }}&#10;Received: {{ number_format($day['in']) }} units&#10;Issued: {{ number_format($day['out']) }} units"
                         aria-label="{{ $date->format('l j F') }}: {{ number_format($day['in']) }} units received, {{ number_format($day['out']) }} units issued">
                        <span class="colchart-bar" style="--bar: var(--ims-series-in); height: {{ $day['in'] / $axisMax * 100 }}%"
                              @if ($day['in'] > 0) data-nonzero @endif></span>
                        <span class="colchart-bar" style="--bar: var(--ims-series-out); height: {{ $day['out'] / $axisMax * 100 }}%"
                              @if ($day['out'] > 0) data-nonzero @endif></span>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <div class="colchart-labels" aria-hidden="true">
        @foreach ($days as $day)
            @php($date = $asDate($day['date']))
            <span @class(['is-today' => $date->isToday()])>
                <span class="d-none d-sm-inline">{{ $date->isToday() ? 'Today' : $date->format('D j') }}</span>
                <span class="d-sm-none">{{ $date->format('j') }}</span>
            </span>
        @endforeach
    </div>
</figure>

<table class="visually-hidden">
    <caption id="movement-table-caption">Units received and issued per day</caption>
    <thead><tr><th scope="col">Date</th><th scope="col">Received</th><th scope="col">Issued</th></tr></thead>
    <tbody>
        @foreach ($days as $day)
            <tr><th scope="row">{{ $asDate($day['date'])->format('l j F Y') }}</th><td>{{ $day['in'] }}</td><td>{{ $day['out'] }}</td></tr>
        @endforeach
    </tbody>
</table>
