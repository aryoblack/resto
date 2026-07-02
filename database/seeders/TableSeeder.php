<?php

namespace Database\Seeders;

use App\Models\Table;
use Illuminate\Database\Seeder;

class TableSeeder extends Seeder
{
    /**
     * Create starter dining tables with stable QR tokens.
     */
    public function run(): void
    {
        foreach (range(1, 12) as $number) {
            $tableNumber = 'T' . str_pad((string) $number, 2, '0', STR_PAD_LEFT);

            Table::updateOrCreate(
                ['table_number' => $tableNumber],
                [
                    'qr_code' => hash('sha256', 'resto-app-table-' . $tableNumber),
                    'status' => 'available',
                ],
            );
        }
    }
}
