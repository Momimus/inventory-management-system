<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_transactions', function (Blueprint $table) {
            // Delivery note, invoice or order number the movement relates to.
            $table->string('reference', 100)->nullable()->after('balance_after')->index();
            $table->index(['type', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('stock_transactions', function (Blueprint $table) {
            $table->dropIndex(['type', 'created_at']);
            $table->dropIndex(['reference']);
            $table->dropColumn('reference');
        });
    }
};
