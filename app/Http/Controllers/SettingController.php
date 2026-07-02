<?php

namespace App\Http\Controllers;

use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

class SettingController extends Controller
{
    private const DEFAULT_TAX_PERCENTAGE = 11.0;
    private const DEFAULT_SERVICE_CHARGE_PERCENTAGE = 5.0;

    /**
     * Get all system settings.
     */
    public function index()
    {
        $settings = Cache::remember('system_settings_all', 86400, function () {
            return SystemSetting::pluck('value', 'key');
        });

        return response()->json([
            'message' => 'Pengaturan sistem berhasil diambil.',
            'data' => $settings,
        ]);
    }

    /**
     * Get public billing settings used by the customer cart.
     */
    public function publicBilling()
    {
        return response()->json([
            'message' => 'Pengaturan biaya berhasil diambil.',
            'data' => [
                'tax_percentage' => (float) SystemSetting::getValue(
                    'tax_percentage',
                    self::DEFAULT_TAX_PERCENTAGE
                ),
                'service_charge_percentage' => (float) SystemSetting::getValue(
                    'service_charge_percentage',
                    self::DEFAULT_SERVICE_CHARGE_PERCENTAGE
                ),
            ],
        ]);
    }

    /**
     * Get public receipt settings used by cashier print views.
     */
    public function publicReceipt()
    {
        return response()->json([
            'message' => 'Pengaturan struk berhasil diambil.',
            'data' => $this->receiptIdentitySettings(),
        ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }

    /**
     * Get receipt and printer settings for authenticated staff print flows.
     */
    public function staffReceipt()
    {
        return response()->json([
            'message' => 'Pengaturan struk dan printer berhasil diambil.',
            'data' => [
                ...$this->receiptIdentitySettings(),
                'thermal_print_method' => SystemSetting::getValue('thermal_print_method', 'browser'),
                'thermal_printer_connection' => SystemSetting::getValue('thermal_printer_connection', 'windows'),
                'thermal_printer_name' => SystemSetting::getValue('thermal_printer_name', ''),
                'thermal_printer_share_name' => SystemSetting::getValue('thermal_printer_share_name', ''),
                'thermal_printer_ip' => SystemSetting::getValue('thermal_printer_ip', ''),
                'thermal_printer_port' => (int) SystemSetting::getValue('thermal_printer_port', 9100),
                'thermal_printer_bluetooth_port' => SystemSetting::getValue('thermal_printer_bluetooth_port', ''),
                'print_bridge_url' => SystemSetting::getValue('print_bridge_url', ''),
                'print_bridge_token' => SystemSetting::getValue('print_bridge_token', ''),
            ],
        ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }

    private function receiptIdentitySettings(): array
    {
        return [
            'restaurant_name' => SystemSetting::getValue('restaurant_name', 'RestoApp'),
            'restaurant_address' => SystemSetting::getValue('restaurant_address', ''),
            'restaurant_phone' => SystemSetting::getValue('restaurant_phone', ''),
            'receipt_slogan' => SystemSetting::getValue('receipt_slogan', 'Terima kasih'),
        ];
    }

    /**
     * Update system settings.
     */
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'settings' => 'required|array',
            'settings.restaurant_name' => 'sometimes|string|max:255',
            'settings.restaurant_logo' => 'sometimes|string',
            'settings.restaurant_address' => 'sometimes|nullable|string|max:500',
            'settings.restaurant_phone' => 'sometimes|nullable|string|max:50',
            'settings.receipt_slogan' => 'sometimes|nullable|string|max:255',
            'settings.operational_hours' => 'sometimes|string|max:255',
            'settings.contact_info' => 'sometimes|string|max:255',
            'settings.point_conversion_rate' => 'sometimes|numeric|min:1',
            'settings.tax_percentage' => 'sometimes|numeric|min:0|max:100',
            'settings.service_charge_percentage' => 'sometimes|numeric|min:0|max:100',
            'settings.qr_print_paper_size' => 'sometimes|in:58,80',
            'settings.thermal_print_method' => 'sometimes|in:browser,windows,escpos,bridge',
            'settings.thermal_printer_connection' => 'sometimes|in:windows,usb,network,bluetooth',
            'settings.thermal_printer_name' => 'sometimes|nullable|string|max:255',
            'settings.thermal_printer_share_name' => 'sometimes|nullable|string|max:255',
            'settings.thermal_printer_ip' => 'sometimes|nullable|ip',
            'settings.thermal_printer_port' => 'sometimes|nullable|integer|min:1|max:65535',
            'settings.thermal_printer_bluetooth_port' => 'sometimes|nullable|string|max:100',
            'settings.print_bridge_url' => 'sometimes|nullable|string|max:255',
            'settings.print_bridge_token' => 'sometimes|nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $settings = $request->input('settings');

        foreach ($settings as $key => $value) {
            SystemSetting::setValue($key, $value);
        }

        return response()->json([
            'message' => 'Pengaturan sistem berhasil diperbarui.',
            'data' => Cache::get('system_settings_all', SystemSetting::pluck('value', 'key'))
        ]);
    }
}
