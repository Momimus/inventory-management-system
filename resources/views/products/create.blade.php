<x-layouts.app title="Add product">
    <x-page-header title="Add product" />

    <form method="POST" action="{{ route('products.store') }}" enctype="multipart/form-data" novalidate>
        @csrf
        @include('products._form')

        <div class="d-flex gap-2 mt-4">
            <x-submit-button>Create product</x-submit-button>
            <a href="{{ route('products.index') }}" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </form>
</x-layouts.app>
