<x-layouts.app title="Add supplier">
    <x-page-header title="Add supplier" />

    <div class="card col-lg-8 col-xl-6">
        <div class="card-body">
            <form method="POST" action="{{ route('suppliers.store') }}" novalidate>
                @csrf
                @include('suppliers._form')

                <div class="d-flex gap-2">
                    <x-submit-button>Create supplier</x-submit-button>
                    <a href="{{ route('suppliers.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</x-layouts.app>
