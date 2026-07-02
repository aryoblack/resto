<?php

namespace Tests\Feature;

use App\Models\SystemSetting;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SettingControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function public_billing_settings_return_only_cart_percentages(): void
    {
        SystemSetting::setValue('tax_percentage', '7.5');
        SystemSetting::setValue('service_charge_percentage', '3');
        SystemSetting::setValue('print_bridge_token', 'secret-token');

        $response = $this->getJson('/api/settings/billing');

        $response->assertOk()
            ->assertJsonPath('data.tax_percentage', 7.5)
            ->assertJsonPath('data.service_charge_percentage', 3);

        $this->assertSame(
            ['tax_percentage', 'service_charge_percentage'],
            array_keys($response->json('data'))
        );
    }

    #[Test]
    public function public_billing_settings_use_defaults_when_missing(): void
    {
        $response = $this->getJson('/api/settings/billing');

        $response->assertOk()
            ->assertJsonPath('data.tax_percentage', 11)
            ->assertJsonPath('data.service_charge_percentage', 5);
    }

    #[Test]
    public function public_receipt_settings_return_only_receipt_identity_fields(): void
    {
        SystemSetting::setValue('restaurant_name', 'Resto App');
        SystemSetting::setValue('restaurant_address', 'Jl. Contoh No. 1');
        SystemSetting::setValue('restaurant_phone', '0812-3456-7890');
        SystemSetting::setValue('receipt_slogan', 'Terima kasih');
        SystemSetting::setValue('print_bridge_token', 'secret-token');

        $response = $this->getJson('/api/settings/receipt');

        $response->assertOk()
            ->assertJsonPath('data.restaurant_name', 'Resto App')
            ->assertJsonPath('data.restaurant_address', 'Jl. Contoh No. 1')
            ->assertJsonPath('data.restaurant_phone', '0812-3456-7890')
            ->assertJsonPath('data.receipt_slogan', 'Terima kasih');

        $this->assertSame(
            ['restaurant_name', 'restaurant_address', 'restaurant_phone', 'receipt_slogan'],
            array_keys($response->json('data'))
        );
    }

    #[Test]
    public function staff_receipt_settings_return_printer_configuration(): void
    {
        $this->seed(RoleSeeder::class);

        $waiter = User::factory()->create(['role' => 'waiter']);
        $waiter->assignRole('waiter');

        SystemSetting::setValue('thermal_print_method', 'escpos');
        SystemSetting::setValue('thermal_printer_connection', 'bluetooth');
        SystemSetting::setValue('thermal_printer_name', 'POS-80C');
        SystemSetting::setValue('thermal_printer_bluetooth_port', 'COM5');
        SystemSetting::setValue('print_bridge_url', 'http://127.0.0.1:17777/print');
        SystemSetting::setValue('print_bridge_token', 'secret-token');

        $response = $this->actingAs($waiter, 'sanctum')
            ->getJson('/api/staff/settings/receipt');

        $response->assertOk()
            ->assertJsonPath('data.thermal_print_method', 'escpos')
            ->assertJsonPath('data.thermal_printer_connection', 'bluetooth')
            ->assertJsonPath('data.thermal_printer_name', 'POS-80C')
            ->assertJsonPath('data.thermal_printer_bluetooth_port', 'COM5')
            ->assertJsonPath('data.print_bridge_url', 'http://127.0.0.1:17777/print')
            ->assertJsonPath('data.print_bridge_token', 'secret-token');
    }
}
