<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('order', function (Blueprint $table) {
            $table->string('order_number', 32)->nullable()->unique()->after('id');
        });

        DB::table('order')
            ->whereNull('order_number')
            ->orderBy('id')
            ->get(['id', 'created_at'])
            ->each(function (object $order): void {
                $date = $order->created_at
                    ? date('Ymd', strtotime((string) $order->created_at))
                    : now()->format('Ymd');

                DB::table('order')
                    ->where('id', $order->id)
                    ->update([
                        'order_number' => 'ORD-' . $date . '-' . str_pad((string) $order->id, 6, '0', STR_PAD_LEFT),
                    ]);
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order', function (Blueprint $table) {
            $table->dropUnique(['order_number']);
            $table->dropColumn('order_number');
        });
    }
};
