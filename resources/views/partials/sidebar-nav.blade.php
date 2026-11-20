{{-- Links are hidden from users who lack permission, but every route also checks permission on the server. --}}
<nav class="app-sidebar-nav" aria-label="Main navigation">
    <x-sidebar-link route="dashboard" icon="speedometer2">Dashboard</x-sidebar-link>

    <div class="app-sidebar-heading">Catalogue</div>
    <x-sidebar-link route="categories.index" active="categories.*" icon="tags">Categories</x-sidebar-link>
    <x-sidebar-link route="suppliers.index" active="suppliers.*" icon="truck">Suppliers</x-sidebar-link>

    @can('viewAny', \App\Models\User::class)
        <div class="app-sidebar-heading">Administration</div>
        <x-sidebar-link route="users.index" active="users.*" icon="people">Users</x-sidebar-link>
    @endcan
</nav>
