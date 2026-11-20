@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <h1 class="h3 mb-4">Dashboard</h1>

    <div class="row g-3">
        <div class="col-sm-6 col-lg-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-body-secondary small">Products</div>
                    <div class="fs-3 fw-semibold">{{ $productCount }}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-body-secondary small">Categories</div>
                    <div class="fs-3 fw-semibold">{{ $categoryCount }}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card h-100 border-warning">
                <div class="card-body">
                    <div class="text-body-secondary small">Low stock</div>
                    <div class="fs-3 fw-semibold">{{ $lowStockCount }}</div>
                </div>
            </div>
        </div>
    </div>
@endsection
