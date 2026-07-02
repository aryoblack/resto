<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds all recommended database indexes from design.md for query performance.
     */
    public function up(): void
    {
        // Performa query pesanan aktif
        Schema::table('order', function (Blueprint $table) {
            $table->index('order_status', 'idx_orders_status');
            $table->index('table_id', 'idx_orders_table_id');
            $table->index('created_at', 'idx_orders_created_at');
        });

        // Performa filter menu
        Schema::table('menu', function (Blueprint $table) {
            $table->index(['category_id', 'is_available'], 'idx_menus_category_available');
        });

        // Performa cek stok kritis
        Schema::table('inventory', function (Blueprint $table) {
            $table->index(['current_stock', 'min_stock'], 'idx_inventory_stock');
        });

        // Performa cek reservasi konflik
        Schema::table('reservation', function (Blueprint $table) {
            $table->index(['table_id', 'date', 'time'], 'idx_reservations_table_date');
        });

        // Performa riwayat poin
        Schema::table('point_transaction', function (Blueprint $table) {
            $table->index(['user_id', 'created_at'], 'idx_point_transactions_user');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order', function (Blueprint $table) {
            $table->dropIndex('idx_orders_status');
            $table->dropIndex('idx_orders_table_id');
            $table->dropIndex('idx_orders_created_at');
        });

        Schema::table('menu', function (Blueprint $table) {
            $table->dropIndex('idx_menus_category_available');
        });

        Schema::table('inventory', function (Blueprint $table) {
            $table->dropIndex('idx_inventory_stock');
        });

        Schema::table('reservation', function (Blueprint $table) {
            $table->dropIndex('idx_reservations_table_date');
        });

        Schema::table('point_transaction', function (Blueprint $table) {
            $table->dropIndex('idx_point_transactions_user');
        });
    }
};
