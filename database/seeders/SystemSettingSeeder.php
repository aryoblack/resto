<?php

namespace Database\Seeders;

use App\Models\SystemSetting;
use Illuminate\Database\Seeder;

class SystemSettingSeeder extends Seeder
{
    /**
     * Create default system settings.
     */
    public function run(): void
    {
        $settings = [
            'restaurant_name' => 'Resto App',
            'restaurant_address' => '',
            'restaurant_phone' => '',
            'receipt_slogan' => 'Terima kasih atas kunjungan Anda',
            'tax_percentage' => '11',
            'service_charge_percentage' => '5',
            'point_conversion_rate' => '10000', // Rp 10.000 per 1 poin
            'logo_url' => '',
            'operating_hours_open' => '08:00',
            'operating_hours_close' => '22:00',
            'qr_print_paper_size' => '80',
            'thermal_print_method' => 'browser',
            'thermal_printer_connection' => 'windows',
            'thermal_printer_name' => '',
            'thermal_printer_share_name' => '',
            'thermal_printer_ip' => '',
            'thermal_printer_port' => '9100',
            'thermal_printer_bluetooth_port' => '',
            'print_bridge_url' => '',
            'print_bridge_token' => '',
        ];

        foreach ($settings as $key => $value) {
            SystemSetting::updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }
    }
}
