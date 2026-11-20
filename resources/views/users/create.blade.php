<x-layouts.app title="Add user">
    <x-page-header title="Add user" />

    <div class="card col-lg-8 col-xl-6">
        <div class="card-body">
            <form method="POST" action="{{ route('users.store') }}" novalidate>
                @csrf
                @include('users._form')

                <div class="d-flex gap-2">
                    <x-submit-button>Create user</x-submit-button>
                    <a href="{{ route('users.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</x-layouts.app>
