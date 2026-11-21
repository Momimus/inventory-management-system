<?php

return [

    // ISO 4217 code used when showing prices and stock value.
    'currency' => env('INVENTORY_CURRENCY', 'USD'),

    'product_images' => [
        // "public" stores files on this server. Hosts without a persistent disk
        // (such as Vercel containers) must use "s3" or another shared disk.
        'disk' => env('PRODUCT_IMAGE_DISK', 'public'),
        'directory' => 'products',
        'max_upload_kb' => 2048,
        'max_input_dimension' => 4000,
        // Stored images are resized so neither side is larger than this.
        'max_stored_dimension' => 800,
    ],

    // How long dashboard figures are cached; changes to stock clear the cache immediately.
    'dashboard_cache_seconds' => 300,

];
