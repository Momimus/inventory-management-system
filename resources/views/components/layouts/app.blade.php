@props(['title'])

@php
    $user = auth()->user();
    $initials = collect(preg_split('/\s+/', trim($user->name)))->filter()->take(2)
        ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->implode('');
    $sidebarCollapsed = request()->cookie('ims_sidebar') === 'collapsed';
@endphp

<!DOCTYPE html>
{{-- The sidebar state is rendered on the server so the layout never jumps on load (inline scripts are blocked by the CSP). --}}
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" @class(['sidebar-collapsed' => $sidebarCollapsed])>
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
                <a href="{{ route('dashboard') }}" class="app-brand" id="appSidebarLabel" title="{{ config('app.name') }}">
                    <span class="app-brand-mark" aria-hidden="true"><i class="bi bi-box-seam"></i></span>
                    <span class="app-brand-name">{{ config('app.name') }}</span>
                </a>
                <button type="button" class="btn-close btn-close-white d-lg-none" data-bs-dismiss="offcanvas"
                        data-bs-target="#appSidebar" aria-label="Close menu"></button>
            </div>

            @include('partials.sidebar-nav')

            <div class="app-sidebar-footer">Signed in as {{ $user->role->label() }}</div>
        </aside>

        <div class="app-main">
            <header class="app-topbar">
                <button class="app-icon-button d-lg-none" type="button" data-bs-toggle="offcanvas"
                        data-bs-target="#appSidebar" aria-controls="appSidebar" aria-label="Open menu">
                    <i class="bi bi-list" aria-hidden="true"></i>
                </button>
                <button class="app-icon-button d-none d-lg-inline-flex" type="button" data-sidebar-toggle
                        aria-controls="appSidebar" aria-expanded="{{ $sidebarCollapsed ? 'false' : 'true' }}"
                        aria-label="{{ $sidebarCollapsed ? 'Expand sidebar' : 'Collapse sidebar' }}">
                    <i class="bi bi-layout-sidebar" aria-hidden="true"></i>
                </button>

                <form method="GET" action="{{ route('products.index') }}" class="app-search d-none d-md-block" role="search">
                    <label for="globalSearch" class="visually-hidden">Search products</label>
                    <i class="bi bi-search" aria-hidden="true"></i>
                    <input type="search" name="search" id="globalSearch" class="form-control" maxlength="100"
                           placeholder="Search products by name or SKU"
                           value="{{ request()->routeIs('products.index') ? request('search') : '' }}">
                </form>

                <a href="{{ route('dashboard') }}" class="fw-semibold text-body text-decoration-none d-md-none text-truncate">
                    {{ config('app.name') }}
                </a>

                <div class="ms-auto d-flex align-items-center gap-3">
                    <time class="app-topbar-date d-none d-xl-inline" datetime="{{ now()->toDateString() }}">
                        {{ now()->format('l, j F Y') }}
                    </time>

                    <div class="dropdown">
                        <button class="app-user-button" type="button" data-bs-toggle="dropdown" aria-expanded="false"
                                aria-label="Account menu for {{ $user->name }}">
                            <span class="avatar" aria-hidden="true">{{ $initials }}</span>
                            <span class="app-user-meta d-none d-sm-flex">
                                <span class="app-user-name">{{ $user->name }}</span>
                                <span class="app-user-role">{{ $user->role->label() }}</span>
                            </span>
                            <i class="bi bi-chevron-down small text-muted-ims d-none d-sm-inline" aria-hidden="true"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" style="min-width: 15rem">
                            <li class="px-3 pt-2 pb-2">
                                <div class="fw-semibold text-truncate">{{ $user->name }}</div>
                                <div class="small text-body-secondary text-truncate">{{ $user->email }}</div>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="{{ route('profile.edit') }}"><i class="bi bi-person" aria-hidden="true"></i>My profile</a></li>
                            <li>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="dropdown-item"><i class="bi bi-box-arrow-right" aria-hidden="true"></i>Sign out</button>
                                </form>
                            </li>
                        </ul>
                    </div>
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
