<x-layouts.app title="Edit category">
    <x-page-header :title="'Edit '.$category->name" />

    <div class="card col-lg-8 col-xl-6">
        <div class="card-body">
            <form method="POST" action="{{ route('categories.update', $category) }}" novalidate>
                @csrf
                @method('PUT')
                @include('categories._form')

                <div class="d-flex gap-2">
                    <x-submit-button>Save changes</x-submit-button>
                    <a href="{{ route('categories.show', $category) }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</x-layouts.app>
