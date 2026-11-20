<x-layouts.app title="Edit product">
    <x-page-header :title="'Edit '.$product->name" :subtitle="$product->sku" />

    <form method="POST" action="{{ route('products.update', $product) }}" enctype="multipart/form-data" novalidate>
        @csrf
        @method('PUT')
        @include('products._form')

        <div class="d-flex gap-2 mt-4">
            <x-submit-button>Save changes</x-submit-button>
            <a href="{{ route('products.show', $product) }}" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </form>
</x-layouts.app>
