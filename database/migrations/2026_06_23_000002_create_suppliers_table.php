<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('contact_person')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('inventory_supplier', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_id')->constrained('inventory')->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
            $table->decimal('last_price', 12, 2)->nullable();
            $table->unsignedInteger('lead_time_days')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
            $table->unique(['inventory_id', 'supplier_id']);
        });

        $now = now();
        $legacySuppliers = DB::table('inventory')
            ->whereNotNull('supplier')
            ->where('supplier', '<>', '')
            ->select('id', 'supplier')
            ->get();

        foreach ($legacySuppliers as $item) {
            $supplierName = trim((string) $item->supplier);
            if ($supplierName === '') {
                continue;
            }

            $supplier = DB::table('suppliers')->where('name', $supplierName)->first();

            $supplierId = $supplier?->id ?? DB::table('suppliers')->insertGetId([
                'name' => $supplierName,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('inventory_supplier')->updateOrInsert(
                [
                    'inventory_id' => $item->id,
                    'supplier_id' => $supplierId,
                ],
                [
                    'is_primary' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_supplier');
        Schema::dropIfExists('suppliers');
    }
};
