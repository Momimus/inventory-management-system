@props(['title'])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ $title }} | {{ config('app.name') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="app-body">
    <main class="guest-shell">
        <div class="guest-card card shadow-sm">
            <div class="card-body p-4 p-sm-5">
                <div class="text-center mb-4">
                    <i class="bi bi-box-seam fs-1 text-primary" aria-hidden="true"></i>
                    <div class="fw-semibold mt-1">{{ config('app.name') }}</div>
                </div>

                <x-flash />

                {{ $slot }}
            </div>
        </div>
    </main>
</body>
</html>
