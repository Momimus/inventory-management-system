<x-layouts.app title="Add category">
    <x-page-header title="Add category" />

    <div class="card col-lg-8 col-xl-6">
        <div class="card-body">
            <form method="POST" action="{{ route('categories.store') }}" novalidate>
                @csrf
                @include('categories._form')

                <div class="d-flex gap-2">
                    <x-submit-button>Create category</x-submit-button>
                    <a href="{{ route('categories.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</x-layouts.app>
