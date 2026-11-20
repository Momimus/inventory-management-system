<x-layouts.app title="Edit supplier">
    <x-page-header :title="'Edit '.$supplier->name" />

    <div class="card col-lg-8 col-xl-6">
        <div class="card-body">
            <form method="POST" action="{{ route('suppliers.update', $supplier) }}" novalidate>
                @csrf
                @method('PUT')
                @include('suppliers._form')

                <div class="d-flex gap-2">
                    <x-submit-button>Save changes</x-submit-button>
                    <a href="{{ route('suppliers.show', $supplier) }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</x-layouts.app>
