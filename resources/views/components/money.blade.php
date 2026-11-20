@props(['amount'])

{{ \Illuminate\Support\Number::currency((float) $amount, in: config('inventory.currency')) }}
