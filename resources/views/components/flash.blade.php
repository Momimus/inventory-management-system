@if (session('success'))
    <div class="alert alert-success alert-dismissible fade show d-flex align-items-start" role="status">
        <i class="bi bi-check-circle me-2" aria-hidden="true"></i>
        <div>{{ session('success') }}</div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if (session('error'))
    <div class="alert alert-danger alert-dismissible fade show d-flex align-items-start" role="alert">
        <i class="bi bi-exclamation-triangle me-2" aria-hidden="true"></i>
        <div>{{ session('error') }}</div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif
