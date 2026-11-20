<x-layouts.app title="Edit user">
    <x-page-header :title="'Edit '.$user->name">
        <x-status-badge :active="$user->is_active" />
    </x-page-header>

    <div class="card col-lg-8 col-xl-6">
        <div class="card-body">
            <form method="POST" action="{{ route('users.update', $user) }}" novalidate>
                @csrf
                @method('PUT')
                @include('users._form')

                <div class="d-flex gap-2">
                    <x-submit-button>Save changes</x-submit-button>
                    <a href="{{ route('users.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</x-layouts.app>
