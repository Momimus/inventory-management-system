@props(['title'])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title }} | {{ config('app.name') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="app-body">
    <div class="app-shell">
        <aside class="offcanvas-lg offcanvas-start app-sidebar" tabindex="-1" id="appSidebar" aria-labelledby="appSidebarLabel">
            <div class="app-sidebar-brand">
                <a href="{{ route('dashboard') }}" class="text-reset text-decoration-none" id="appSidebarLabel">
                    <i class="bi bi-box-seam me-2" aria-hidden="true"></i>{{ config('app.name') }}
                </a>
                <button type="button" class="btn-close btn-close-white d-lg-none" data-bs-dismiss="offcanvas"
                        data-bs-target="#appSidebar" aria-label="Close menu"></button>
            </div>

            @include('partials.sidebar-nav')
        </aside>

        <div class="app-main">
            <header class="app-topbar">
                <button class="btn btn-link text-body d-lg-none px-0 me-2" type="button" data-bs-toggle="offcanvas"
                        data-bs-target="#appSidebar" aria-controls="appSidebar" aria-label="Open menu">
                    <i class="bi bi-list fs-4" aria-hidden="true"></i>
                </button>
                <a href="{{ route('dashboard') }}" class="fw-semibold text-body text-decoration-none d-lg-none text-truncate">
                    {{ config('app.name') }}
                </a>

                <div class="dropdown ms-auto">
                    <button class="btn btn-link text-body text-decoration-none dropdown-toggle" type="button"
                            data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-person-circle me-1" aria-hidden="true"></i>
                        <span class="d-none d-sm-inline">{{ auth()->user()->name }}</span>
                        <span class="badge text-bg-light border ms-1 d-none d-md-inline">{{ auth()->user()->role->label() }}</span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="{{ route('profile.edit') }}"><i class="bi bi-person me-2"></i>My profile</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="dropdown-item"><i class="bi bi-box-arrow-right me-2"></i>Sign out</button>
                            </form>
                        </li>
                    </ul>
                </div>
            </header>

            <main class="app-content">
                <x-flash />

                {{ $slot }}
            </main>
        </div>
    </div>

    <x-confirm-modal />
</body>
</html>
