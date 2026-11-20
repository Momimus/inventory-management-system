<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Renaming keeps any existing prices: the old single price was the selling price.
        Schema::table('products', function (Blueprint $table) {
            $table->renameColumn('unit_price', 'selling_price');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->decimal('purchase_price', 10, 2)->default(0)->after('description');
            $table->string('image_path')->nullable()->after('reorder_level');
            $table->boolean('is_active')->default(true)->after('image_path')->index();
            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['name']);
            $table->dropIndex(['is_active']);
            $table->dropColumn(['purchase_price', 'image_path', 'is_active']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->renameColumn('selling_price', 'unit_price');
        });
    }
};
