<x-layouts.app title="Users">
    <x-page-header title="Users" subtitle="Who can sign in and what they are allowed to do.">
        <a href="{{ route('users.create') }}" class="btn btn-primary"><i class="bi bi-person-plus me-1"></i>Add user</a>
    </x-page-header>

    <x-filter-bar :action="route('users.index')" :search="$filters['search'] ?? null" placeholder="Name or email">
        <x-filter-select name="role" label="Role" :options="\App\Enums\UserRole::options()" :value="$filters['role'] ?? null" />
        <x-filter-select name="status" label="Status" :options="['active' => 'Active', 'inactive' => 'Inactive']" :value="$filters['status'] ?? null" />
    </x-filter-bar>

    <div class="card">
        @if ($users->isEmpty())
            <x-empty-state icon="people" title="No users found" message="Try a different search or filter." />
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th scope="col">Name</th>
                            <th scope="col">Email</th>
                            <th scope="col">Role</th>
                            <th scope="col">Status</th>
                            <th scope="col">Last sign-in</th>
                            <th scope="col" class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($users as $user)
                            <tr>
                                <td class="fw-medium">
                                    {{ $user->name }}
                                    @if ($user->is(auth()->user()))
                                        <span class="badge text-bg-light border ms-1">You</span>
                                    @endif
                                </td>
                                <td>{{ $user->email }}</td>
                                <td>{{ $user->role->label() }}</td>
                                <td><x-status-badge :active="$user->is_active" /></td>
                                <td class="text-body-secondary small">{{ $user->last_login_at?->diffForHumans() ?? 'Never' }}</td>
                                <td class="text-end text-nowrap">
                                    <a href="{{ route('users.edit', $user) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                                    @can('changeAccess', $user)
                                        <form method="POST" action="{{ route('users.status', $user) }}" class="d-inline"
                                              data-confirm="{{ $user->is_active ? "Deactivate {$user->name}? They will be signed out and unable to sign in." : "Reactivate {$user->name}?" }}"
                                              data-confirm-button="{{ $user->is_active ? 'Deactivate' : 'Activate' }}">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" @class(['btn btn-sm', 'btn-outline-danger' => $user->is_active, 'btn-outline-success' => ! $user->is_active])>
                                                {{ $user->is_active ? 'Deactivate' : 'Activate' }}
                                            </button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <div class="mt-3">{{ $users->links() }}</div>
</x-layouts.app>
