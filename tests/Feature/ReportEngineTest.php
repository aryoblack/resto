<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;

use App\Exports\SalesReportExport;
use App\Exports\StockReportExport;
use App\Models\Category;
use App\Models\Inventory;
use App\Models\Menu;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Services\ReportService;
use Carbon\Carbon;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

/**
 * Feature tests for the Report_Engine module — Tasks 13.2, 13.3 & 13.4.
 *
 * Covers:
 *   - getSalesReport() returns grouped data for daily/weekly/monthly periods
 *   - Each grouped row contains period_label, total_revenue, order_count, average_order_value
 *   - Summary totals are correct (total omzet, jumlah pesanan, rata-rata nilai pesanan)
 *   - Only paid orders are included
 *   - Explicit date_from/date_to filter works
 *   - Default date ranges are sensible (daily=30d, weekly=12w, monthly=12m)
 *   - top_menus returns top 5 menus by quantity with correct fields
 *   - payment_method_breakdown returns count and revenue per payment method
 *   - HTTP endpoint returns correct structure
 *   - Non-admin cannot access the endpoint
 *   - getStockReport() returns all inventory items with current_stock vs min_stock comparison
 *   - is_critical flag is set correctly (current_stock ≤ min_stock)
 *   - summary counts (total_items, critical_count) are accurate
 *   - Stock report HTTP endpoint returns correct structure and is admin-only
 *
 * Validates: Requirements 15.1, 15.2, 15.3
 */
class ReportEngineTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $customer;
    private Category $category;
    private Menu $menu;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->admin->assignRole('admin');

        $this->customer = User::factory()->create(['role' => 'customer']);
        $this->customer->assignRole('customer');

        $this->category = Category::create(['name' => 'Makanan', 'sort_order' => 1]);

        $this->menu = Menu::create([
            'name'         => 'Nasi Goreng',
            'category_id'  => $this->category->id,
            'price'        => 25000,
            'stock'        => 100,
            'is_available' => true,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helper
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Create a paid order with one item at the given timestamp.
     */
    private function createPaidOrder(float $price, Carbon $at, string $paymentMethod = 'cash'): Order
    {
        $order = Order::create([
            'user_id'        => $this->customer->id,
            'total_price'    => $price,
            'payment_status' => 'paid',
            'order_status'   => 'Disajikan',
            'payment_method' => $paymentMethod,
            'order_type'     => 'dine_in',
        ]);

        OrderItem::create([
            'order_id'      => $order->id,
            'menu_id'       => $this->menu->id,
            'quantity'      => 1,
            'price_at_time' => $price,
        ]);

        // Manually set created_at to the desired timestamp
        $order->created_at = $at;
        $order->save();

        return $order;
    }

    /**
     * Create a paid order with a specific menu item and quantity.
     */
    private function createPaidOrderWithItem(Menu $menu, int $quantity, float $pricePerItem, Carbon $at, string $paymentMethod = 'cash'): Order
    {
        $totalPrice = $pricePerItem * $quantity;

        $order = Order::create([
            'user_id'        => $this->customer->id,
            'total_price'    => $totalPrice,
            'payment_status' => 'paid',
            'order_status'   => 'Disajikan',
            'payment_method' => $paymentMethod,
            'order_type'     => 'dine_in',
        ]);

        OrderItem::create([
            'order_id'      => $order->id,
            'menu_id'       => $menu->id,
            'quantity'      => $quantity,
            'price_at_time' => $pricePerItem,
        ]);

        $order->created_at = $at;
        $order->save();

        return $order;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Unit-level tests on ReportService
    // ─────────────────────────────────────────────────────────────────────────

    /**
     */
    #[Test]
    public function test_daily_report_groups_orders_by_day(): void
    {
        $day1 = Carbon::parse('2025-01-10 12:00:00');
        $day2 = Carbon::parse('2025-01-11 14:00:00');

        $this->createPaidOrder(50000, $day1);
        $this->createPaidOrder(30000, $day1);
        $this->createPaidOrder(20000, $day2);

        $service = app(ReportService::class);
        $result  = $service->getSalesReport('daily', '2025-01-10', '2025-01-11');

        $this->assertArrayHasKey('data', $result);
        $this->assertCount(2, $result['data']);

        // First bucket: 2025-01-10 — two orders totalling 80000
        $bucket1 = $result['data'][0];
        $this->assertEquals(80000.0, $bucket1['total_revenue']);
        $this->assertEquals(2, $bucket1['order_count']);
        $this->assertEquals(40000.0, $bucket1['average_order_value']);

        // Second bucket: 2025-01-11 — one order of 20000
        $bucket2 = $result['data'][1];
        $this->assertEquals(20000.0, $bucket2['total_revenue']);
        $this->assertEquals(1, $bucket2['order_count']);
        $this->assertEquals(20000.0, $bucket2['average_order_value']);
    }

    /**
     */
    #[Test]
    public function test_daily_report_period_label_format(): void
    {
        $this->createPaidOrder(10000, Carbon::parse('2025-03-05 10:00:00'));

        $service = app(ReportService::class);
        $result  = $service->getSalesReport('daily', '2025-03-05', '2025-03-05');

        $this->assertCount(1, $result['data']);
        // Label should be a non-empty string (e.g. "05 Mar 2025" or locale equivalent)
        $this->assertNotEmpty($result['data'][0]['period_label']);
        $this->assertIsString($result['data'][0]['period_label']);
    }

    /**
     */
    #[Test]
    public function test_weekly_report_groups_orders_by_week(): void
    {
        // Week 1 of 2025 (ISO): 2025-12-30 to 2025-01-05
        // Week 2 of 2025 (ISO): 2025-01-06 to 2025-01-12
        $week1 = Carbon::parse('2025-01-03 10:00:00'); // Week 1
        $week2 = Carbon::parse('2025-01-08 10:00:00'); // Week 2

        $this->createPaidOrder(100000, $week1);
        $this->createPaidOrder(60000, $week2);
        $this->createPaidOrder(40000, $week2);

        $service = app(ReportService::class);
        $result  = $service->getSalesReport('weekly', '2025-01-01', '2025-01-12');

        $this->assertArrayHasKey('data', $result);
        $this->assertGreaterThanOrEqual(2, count($result['data']));

        // Find the week-2 bucket
        $week2Bucket = collect($result['data'])->first(fn ($b) => $b['order_count'] === 2);
        $this->assertNotNull($week2Bucket);
        $this->assertEquals(100000.0, $week2Bucket['total_revenue']);
        $this->assertEquals(50000.0, $week2Bucket['average_order_value']);
    }

    /**
     */
    #[Test]
    public function test_monthly_report_groups_orders_by_month(): void
    {
        $jan = Carbon::parse('2025-01-15 10:00:00');
        $feb = Carbon::parse('2025-02-20 10:00:00');

        $this->createPaidOrder(200000, $jan);
        $this->createPaidOrder(150000, $jan);
        $this->createPaidOrder(100000, $feb);

        $service = app(ReportService::class);
        $result  = $service->getSalesReport('monthly', '2025-01-01', '2025-02-28');

        $this->assertArrayHasKey('data', $result);
        $this->assertCount(2, $result['data']);

        // January bucket
        $janBucket = $result['data'][0];
        $this->assertEquals(350000.0, $janBucket['total_revenue']);
        $this->assertEquals(2, $janBucket['order_count']);
        $this->assertEquals(175000.0, $janBucket['average_order_value']);

        // February bucket
        $febBucket = $result['data'][1];
        $this->assertEquals(100000.0, $febBucket['total_revenue']);
        $this->assertEquals(1, $febBucket['order_count']);
    }

    /**
     */
    #[Test]
    public function test_summary_totals_are_correct(): void
    {
        $this->createPaidOrder(50000, Carbon::parse('2025-01-10 10:00:00'));
        $this->createPaidOrder(30000, Carbon::parse('2025-01-11 10:00:00'));

        $service = app(ReportService::class);
        $result  = $service->getSalesReport('daily', '2025-01-10', '2025-01-11');

        $this->assertArrayHasKey('summary', $result);
        $summary = $result['summary'];

        $this->assertEquals(80000.0, $summary['total_revenue']);
        $this->assertEquals(2, $summary['order_count']);
        $this->assertEquals(40000.0, $summary['avg_order_value']);
    }

    /**
     */
    #[Test]
    public function test_only_paid_orders_are_included(): void
    {
        // Paid order
        $this->createPaidOrder(50000, Carbon::parse('2025-01-10 10:00:00'));

        // Unpaid (pending) order — should be excluded
        $unpaid = Order::create([
            'user_id'        => $this->customer->id,
            'total_price'    => 99999,
            'payment_status' => 'pending',
            'order_status'   => 'Diterima',
            'order_type'     => 'dine_in',
        ]);
        $unpaid->created_at = Carbon::parse('2025-01-10 11:00:00');
        $unpaid->save();

        $service = app(ReportService::class);
        $result  = $service->getSalesReport('daily', '2025-01-10', '2025-01-10');

        $this->assertEquals(50000.0, $result['summary']['total_revenue']);
        $this->assertEquals(1, $result['summary']['order_count']);
    }

    /**
     */
    #[Test]
    public function test_orders_outside_date_range_are_excluded(): void
    {
        $this->createPaidOrder(50000, Carbon::parse('2025-01-10 10:00:00'));
        $this->createPaidOrder(99999, Carbon::parse('2025-01-15 10:00:00')); // outside range

        $service = app(ReportService::class);
        $result  = $service->getSalesReport('daily', '2025-01-10', '2025-01-10');

        $this->assertEquals(50000.0, $result['summary']['total_revenue']);
        $this->assertEquals(1, $result['summary']['order_count']);
    }

    /**
     */
    #[Test]
    public function test_empty_period_returns_empty_data_array(): void
    {
        $service = app(ReportService::class);
        $result  = $service->getSalesReport('daily', '2020-01-01', '2020-01-31');

        $this->assertIsArray($result['data']);
        $this->assertCount(0, $result['data']);
        $this->assertEquals(0.0, $result['summary']['total_revenue']);
        $this->assertEquals(0, $result['summary']['order_count']);
        $this->assertEquals(0.0, $result['summary']['avg_order_value']);
    }

    /**
     */
    #[Test]
    public function test_result_contains_required_keys(): void
    {
        $service = app(ReportService::class);
        $result  = $service->getSalesReport('daily', '2025-01-01', '2025-01-31');

        $this->assertArrayHasKey('period', $result);
        $this->assertArrayHasKey('date_from', $result);
        $this->assertArrayHasKey('date_to', $result);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('summary', $result);
        $this->assertArrayHasKey('top_menus', $result);
        $this->assertArrayHasKey('payment_method_breakdown', $result);
    }

    /**
     */
    #[Test]
    public function test_each_data_row_contains_required_keys(): void
    {
        $this->createPaidOrder(50000, Carbon::parse('2025-01-10 10:00:00'));

        $service = app(ReportService::class);
        $result  = $service->getSalesReport('daily', '2025-01-10', '2025-01-10');

        $this->assertCount(1, $result['data']);
        $row = $result['data'][0];

        $this->assertArrayHasKey('period_label', $row);
        $this->assertArrayHasKey('total_revenue', $row);
        $this->assertArrayHasKey('order_count', $row);
        $this->assertArrayHasKey('average_order_value', $row);
    }

    /**
     */
    #[Test]
    public function test_default_daily_range_covers_last_30_days(): void
    {
        $service = app(ReportService::class);
        $result  = $service->getSalesReport('daily');

        $from = Carbon::parse($result['date_from']);
        $to   = Carbon::parse($result['date_to']);

        // Should span 30 days (today - 29 days to today)
        $this->assertEquals(29, $from->diffInDays($to));
        $this->assertTrue($to->isToday());
    }

    /**
     */
    #[Test]
    public function test_default_weekly_range_covers_last_12_weeks(): void
    {
        $service = app(ReportService::class);
        $result  = $service->getSalesReport('weekly');

        $from = Carbon::parse($result['date_from']);
        $to   = Carbon::parse($result['date_to']);

        // Should span approximately 12 weeks (84 days ± a few days for week boundaries)
        $days = $from->diffInDays($to);
        $this->assertGreaterThanOrEqual(77, $days); // at least 11 weeks
        $this->assertLessThanOrEqual(91, $days);    // at most 13 weeks
    }

    /**
     */
    #[Test]
    public function test_default_monthly_range_covers_last_12_months(): void
    {
        $service = app(ReportService::class);
        $result  = $service->getSalesReport('monthly');

        $from = Carbon::parse($result['date_from']);
        $to   = Carbon::parse($result['date_to']);

        // Should span approximately 12 months
        $months = $from->diffInMonths($to);
        $this->assertGreaterThanOrEqual(11, $months);
        $this->assertLessThanOrEqual(12, $months);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // HTTP endpoint tests
    // ─────────────────────────────────────────────────────────────────────────

    /**
     */
    #[Test]
    public function test_admin_can_access_sales_report_endpoint(): void
    {
        $this->createPaidOrder(50000, Carbon::now());

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/reports/sales?period=daily');

        $response->assertOk()
            ->assertJsonStructure([
                'message',
                'data' => [
                    'period',
                    'date_from',
                    'date_to',
                    'data',
                    'summary' => ['total_revenue', 'order_count', 'avg_order_value'],
                    'top_menus',
                    'payment_method_breakdown',
                ],
            ]);
    }

    /**
     */
    #[Test]
    public function test_sales_report_endpoint_accepts_period_filter(): void
    {
        foreach (['daily', 'weekly', 'monthly'] as $period) {
            $response = $this->actingAs($this->admin, 'sanctum')
                ->getJson("/api/admin/reports/sales?period={$period}");

            $response->assertOk();
            $this->assertEquals($period, $response->json('data.period'));
        }
    }

    /**
     */
    #[Test]
    public function test_sales_report_endpoint_accepts_date_range(): void
    {
        $this->createPaidOrder(75000, Carbon::parse('2025-06-15 10:00:00'));

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/reports/sales?period=daily&date_from=2025-06-15&date_to=2025-06-15');

        $response->assertOk();
        $this->assertEquals('2025-06-15', $response->json('data.date_from'));
        $this->assertEquals('2025-06-15', $response->json('data.date_to'));
        $this->assertEquals(75000.0, $response->json('data.summary.total_revenue'));
    }

    /**
     */
    #[Test]
    public function test_sales_report_endpoint_rejects_invalid_period(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/reports/sales?period=yearly');

        $response->assertUnprocessable();
    }

    /**
     */
    #[Test]
    public function test_customer_cannot_access_sales_report_endpoint(): void
    {
        $response = $this->actingAs($this->customer, 'sanctum')
            ->getJson('/api/admin/reports/sales');

        $response->assertForbidden();
    }

    /**
     */
    #[Test]
    public function test_unauthenticated_cannot_access_sales_report_endpoint(): void
    {
        $response = $this->getJson('/api/admin/reports/sales');

        $response->assertUnauthorized();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Task 13.3 — Metric: top_menus (menu terlaris)
    // Validates: Requirement 15.2
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * top_menus returns the correct menu name, total_quantity, and total_revenue.
     */
    #[Test]
    public function test_top_menus_contains_correct_fields(): void
    {
        $at = Carbon::parse('2025-01-10 10:00:00');

        // Create an order with 3 units of $this->menu at 25000 each
        $order = Order::create([
            'user_id'        => $this->customer->id,
            'total_price'    => 75000,
            'payment_status' => 'paid',
            'order_status'   => 'Disajikan',
            'payment_method' => 'cash',
            'order_type'     => 'dine_in',
        ]);
        OrderItem::create([
            'order_id'      => $order->id,
            'menu_id'       => $this->menu->id,
            'quantity'      => 3,
            'price_at_time' => 25000,
        ]);
        $order->created_at = $at;
        $order->save();

        $service = app(ReportService::class);
        $result  = $service->getSalesReport('daily', '2025-01-10', '2025-01-10');

        $this->assertArrayHasKey('top_menus', $result);
        $this->assertCount(1, $result['top_menus']);

        $topMenu = $result['top_menus'][0];
        $this->assertArrayHasKey('menu_name', $topMenu);
        $this->assertArrayHasKey('total_quantity', $topMenu);
        $this->assertArrayHasKey('total_revenue', $topMenu);

        $this->assertEquals('Nasi Goreng', $topMenu['menu_name']);
        $this->assertEquals(3, $topMenu['total_quantity']);
        $this->assertEquals(75000.0, $topMenu['total_revenue']);
    }

    /**
     * top_menus is sorted by total_quantity descending.
     */
    #[Test]
    public function test_top_menus_sorted_by_quantity_descending(): void
    {
        $at = Carbon::parse('2025-01-10 10:00:00');

        // Create a second menu
        $menu2 = Menu::create([
            'name'         => 'Mie Goreng',
            'category_id'  => $this->category->id,
            'price'        => 20000,
            'stock'        => 100,
            'is_available' => true,
        ]);

        // menu2 sold 5 units, $this->menu sold 2 units
        $this->createPaidOrderWithItem($menu2, 5, 20000, $at);
        $this->createPaidOrderWithItem($this->menu, 2, 25000, $at);

        $service = app(ReportService::class);
        $result  = $service->getSalesReport('daily', '2025-01-10', '2025-01-10');

        $topMenus = $result['top_menus'];
        $this->assertGreaterThanOrEqual(2, count($topMenus));

        // First entry should be the one with highest quantity (menu2 with 5)
        $this->assertEquals('Mie Goreng', $topMenus[0]['menu_name']);
        $this->assertEquals(5, $topMenus[0]['total_quantity']);

        // Second entry should be Nasi Goreng with 2
        $this->assertEquals('Nasi Goreng', $topMenus[1]['menu_name']);
        $this->assertEquals(2, $topMenus[1]['total_quantity']);
    }

    /**
     * top_menus returns at most 5 menus even when more exist.
     */
    #[Test]
    public function test_top_menus_limited_to_five(): void
    {
        $at = Carbon::parse('2025-01-10 10:00:00');

        // Create 6 different menus and sell each once
        for ($i = 1; $i <= 6; $i++) {
            $menu = Menu::create([
                'name'         => "Menu {$i}",
                'category_id'  => $this->category->id,
                'price'        => 10000 * $i,
                'stock'        => 100,
                'is_available' => true,
            ]);
            $this->createPaidOrderWithItem($menu, $i, 10000 * $i, $at);
        }

        $service = app(ReportService::class);
        $result  = $service->getSalesReport('daily', '2025-01-10', '2025-01-10');

        $this->assertCount(5, $result['top_menus']);
    }

    /**
     * top_menus aggregates quantities across multiple orders for the same menu.
     */
    #[Test]
    public function test_top_menus_aggregates_across_multiple_orders(): void
    {
        $at = Carbon::parse('2025-01-10 10:00:00');

        // Two separate orders for the same menu
        $this->createPaidOrderWithItem($this->menu, 2, 25000, $at);
        $this->createPaidOrderWithItem($this->menu, 3, 25000, $at);

        $service = app(ReportService::class);
        $result  = $service->getSalesReport('daily', '2025-01-10', '2025-01-10');

        $this->assertCount(1, $result['top_menus']);
        $this->assertEquals(5, $result['top_menus'][0]['total_quantity']);
        $this->assertEquals(125000.0, $result['top_menus'][0]['total_revenue']);
    }

    /**
     * top_menus is empty when there are no paid orders.
     */
    #[Test]
    public function test_top_menus_empty_when_no_orders(): void
    {
        $service = app(ReportService::class);
        $result  = $service->getSalesReport('daily', '2020-01-01', '2020-01-31');

        $this->assertIsArray($result['top_menus']);
        $this->assertCount(0, $result['top_menus']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Task 13.3 — Metric: payment_method_breakdown (rincian per metode pembayaran)
    // Validates: Requirement 15.2
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * payment_method_breakdown contains count and revenue per payment method.
     */
    #[Test]
    public function test_payment_method_breakdown_contains_correct_fields(): void
    {
        $at = Carbon::parse('2025-01-10 10:00:00');

        $this->createPaidOrder(50000, $at, 'cash');

        $service = app(ReportService::class);
        $result  = $service->getSalesReport('daily', '2025-01-10', '2025-01-10');

        $this->assertArrayHasKey('payment_method_breakdown', $result);
        $breakdown = $result['payment_method_breakdown'];

        $this->assertArrayHasKey('cash', $breakdown);
        $this->assertArrayHasKey('count', $breakdown['cash']);
        $this->assertArrayHasKey('revenue', $breakdown['cash']);

        $this->assertEquals(1, $breakdown['cash']['count']);
        $this->assertEquals(50000.0, $breakdown['cash']['revenue']);
    }

    /**
     * payment_method_breakdown groups orders by payment method correctly.
     */
    #[Test]
    public function test_payment_method_breakdown_groups_by_method(): void
    {
        $at = Carbon::parse('2025-01-10 10:00:00');

        // 2 cash orders
        $this->createPaidOrder(50000, $at, 'cash');
        $this->createPaidOrder(30000, $at, 'cash');

        // 1 qris order
        $this->createPaidOrder(75000, $at, 'qris');

        // 1 card order
        $this->createPaidOrder(100000, $at, 'card');

        $service = app(ReportService::class);
        $result  = $service->getSalesReport('daily', '2025-01-10', '2025-01-10');

        $breakdown = $result['payment_method_breakdown'];

        // Cash: 2 orders, 80000 total
        $this->assertArrayHasKey('cash', $breakdown);
        $this->assertEquals(2, $breakdown['cash']['count']);
        $this->assertEquals(80000.0, $breakdown['cash']['revenue']);

        // QRIS: 1 order, 75000 total
        $this->assertArrayHasKey('qris', $breakdown);
        $this->assertEquals(1, $breakdown['qris']['count']);
        $this->assertEquals(75000.0, $breakdown['qris']['revenue']);

        // Card: 1 order, 100000 total
        $this->assertArrayHasKey('card', $breakdown);
        $this->assertEquals(1, $breakdown['card']['count']);
        $this->assertEquals(100000.0, $breakdown['card']['revenue']);
    }

    /**
     * payment_method_breakdown only includes paid orders (not pending/failed).
     */
    #[Test]
    public function test_payment_method_breakdown_excludes_unpaid_orders(): void
    {
        $at = Carbon::parse('2025-01-10 10:00:00');

        $this->createPaidOrder(50000, $at, 'cash');

        // Unpaid order — should not appear in breakdown
        $unpaid = Order::create([
            'user_id'        => $this->customer->id,
            'total_price'    => 99999,
            'payment_status' => 'pending',
            'order_status'   => 'Diterima',
            'payment_method' => 'qris',
            'order_type'     => 'dine_in',
        ]);
        $unpaid->created_at = $at;
        $unpaid->save();

        $service = app(ReportService::class);
        $result  = $service->getSalesReport('daily', '2025-01-10', '2025-01-10');

        $breakdown = $result['payment_method_breakdown'];

        // Only cash should appear
        $this->assertArrayHasKey('cash', $breakdown);
        $this->assertArrayNotHasKey('qris', $breakdown);
    }

    /**
     * payment_method_breakdown is empty when there are no paid orders.
     */
    #[Test]
    public function test_payment_method_breakdown_empty_when_no_orders(): void
    {
        $service = app(ReportService::class);
        $result  = $service->getSalesReport('daily', '2020-01-01', '2020-01-31');

        $this->assertIsArray($result['payment_method_breakdown']);
        $this->assertCount(0, $result['payment_method_breakdown']);
    }

    /**
     * HTTP endpoint response includes top_menus and payment_method_breakdown with correct structure.
     * Validates: Requirement 15.2
     */
    #[Test]
    public function test_sales_report_endpoint_includes_metrics_for_requirement_15_2(): void
    {
        $at = Carbon::parse('2025-01-10 10:00:00');

        // Create orders with different payment methods
        $this->createPaidOrder(50000, $at, 'cash');
        $this->createPaidOrder(75000, $at, 'qris');

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/reports/sales?period=daily&date_from=2025-01-10&date_to=2025-01-10');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'summary' => ['total_revenue', 'order_count', 'avg_order_value'],
                    'top_menus' => [
                        '*' => ['menu_name', 'total_quantity', 'total_revenue'],
                    ],
                    'payment_method_breakdown',
                ],
            ]);

        // Verify summary metrics
        $this->assertEquals(125000.0, $response->json('data.summary.total_revenue'));
        $this->assertEquals(2, $response->json('data.summary.order_count'));
        $this->assertEquals(62500.0, $response->json('data.summary.avg_order_value'));

        // Verify payment breakdown
        $breakdown = $response->json('data.payment_method_breakdown');
        $this->assertEquals(1, $breakdown['cash']['count']);
        $this->assertEquals(50000.0, $breakdown['cash']['revenue']);
        $this->assertEquals(1, $breakdown['qris']['count']);
        $this->assertEquals(75000.0, $breakdown['qris']['revenue']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Task 13.4 — Stock opname report (kondisi stok saat ini vs stok minimal)
    // Validates: Requirement 15.3
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Helper: create an inventory item.
     */
    private function createInventoryItem(
        string $name,
        float $currentStock,
        float $minStock,
        string $unit = 'kg',
        ?string $supplier = null,
    ): Inventory {
        return Inventory::create([
            'ingredient_name' => $name,
            'unit'            => $unit,
            'current_stock'   => $currentStock,
            'min_stock'       => $minStock,
            'supplier'        => $supplier,
        ]);
    }

    /**
     * getStockReport() returns the required top-level keys.
     * Validates: Requirement 15.3
     */
    #[Test]
    public function test_stock_report_returns_required_top_level_keys(): void
    {
        $service = app(ReportService::class);
        $result  = $service->getStockReport();

        $this->assertArrayHasKey('items', $result);
        $this->assertArrayHasKey('total_items', $result);
        $this->assertArrayHasKey('critical_count', $result);
    }

    /**
     * Each item in the stock report contains all required fields.
     * Validates: Requirement 15.3
     */
    #[Test]
    public function test_stock_report_items_contain_required_fields(): void
    {
        $this->createInventoryItem('Beras', 50.0, 10.0, 'kg', 'Supplier A');

        $service = app(ReportService::class);
        $result  = $service->getStockReport();

        $this->assertCount(1, $result['items']);
        $item = $result['items'][0];

        $this->assertArrayHasKey('id', $item);
        $this->assertArrayHasKey('ingredient_name', $item);
        $this->assertArrayHasKey('unit', $item);
        $this->assertArrayHasKey('current_stock', $item);
        $this->assertArrayHasKey('min_stock', $item);
        $this->assertArrayHasKey('supplier', $item);
        $this->assertArrayHasKey('is_critical', $item);
    }

    /**
     * is_critical is false when current_stock > min_stock.
     * Validates: Requirement 15.3
     */
    #[Test]
    public function test_stock_report_is_critical_false_when_stock_above_minimum(): void
    {
        $this->createInventoryItem('Tepung', 20.0, 10.0);

        $service = app(ReportService::class);
        $result  = $service->getStockReport();

        $item = $result['items'][0];
        $this->assertFalse($item['is_critical']);
    }

    /**
     * is_critical is true when current_stock equals min_stock (boundary condition).
     * Validates: Requirement 15.3
     */
    #[Test]
    public function test_stock_report_is_critical_true_when_stock_equals_minimum(): void
    {
        $this->createInventoryItem('Gula', 10.0, 10.0);

        $service = app(ReportService::class);
        $result  = $service->getStockReport();

        $item = $result['items'][0];
        $this->assertTrue($item['is_critical']);
    }

    /**
     * is_critical is true when current_stock is below min_stock.
     * Validates: Requirement 15.3
     */
    #[Test]
    public function test_stock_report_is_critical_true_when_stock_below_minimum(): void
    {
        $this->createInventoryItem('Minyak', 3.0, 10.0);

        $service = app(ReportService::class);
        $result  = $service->getStockReport();

        $item = $result['items'][0];
        $this->assertTrue($item['is_critical']);
    }

    /**
     * total_items and critical_count summary counts are accurate.
     * Validates: Requirement 15.3
     */
    #[Test]
    public function test_stock_report_summary_counts_are_accurate(): void
    {
        // 2 normal items, 2 critical items
        $this->createInventoryItem('Beras', 50.0, 10.0);   // normal
        $this->createInventoryItem('Tepung', 20.0, 5.0);   // normal
        $this->createInventoryItem('Gula', 3.0, 10.0);     // critical (below)
        $this->createInventoryItem('Minyak', 5.0, 5.0);    // critical (equal)

        $service = app(ReportService::class);
        $result  = $service->getStockReport();

        $this->assertEquals(4, $result['total_items']);
        $this->assertEquals(2, $result['critical_count']);
    }

    /**
     * Items are returned sorted alphabetically by ingredient_name.
     * Validates: Requirement 15.3
     */
    #[Test]
    public function test_stock_report_items_sorted_alphabetically(): void
    {
        $this->createInventoryItem('Tepung', 20.0, 5.0);
        $this->createInventoryItem('Beras', 50.0, 10.0);
        $this->createInventoryItem('Minyak', 5.0, 5.0);

        $service = app(ReportService::class);
        $result  = $service->getStockReport();

        $names = array_column($result['items'], 'ingredient_name');
        $this->assertEquals(['Beras', 'Minyak', 'Tepung'], $names);
    }

    /**
     * Stock report returns empty items array and zero counts when inventory is empty.
     * Validates: Requirement 15.3
     */
    #[Test]
    public function test_stock_report_empty_when_no_inventory(): void
    {
        $service = app(ReportService::class);
        $result  = $service->getStockReport();

        $this->assertIsArray($result['items']);
        $this->assertCount(0, $result['items']);
        $this->assertEquals(0, $result['total_items']);
        $this->assertEquals(0, $result['critical_count']);
    }

    /**
     * Item data values match the stored inventory record.
     * Validates: Requirement 15.3
     */
    #[Test]
    public function test_stock_report_item_values_match_inventory_record(): void
    {
        $inventory = $this->createInventoryItem('Kecap', 15.5, 20.0, 'liter', 'PT Kecap Manis');

        $service = app(ReportService::class);
        $result  = $service->getStockReport();

        $item = $result['items'][0];
        $this->assertEquals($inventory->id, $item['id']);
        $this->assertEquals('Kecap', $item['ingredient_name']);
        $this->assertEquals('liter', $item['unit']);
        $this->assertEquals(15.5, $item['current_stock']);
        $this->assertEquals(20.0, $item['min_stock']);
        $this->assertEquals('PT Kecap Manis', $item['supplier']);
        $this->assertTrue($item['is_critical']); // 15.5 < 20.0
    }

    /**
     * Supplier field is null when not set.
     * Validates: Requirement 15.3
     */
    #[Test]
    public function test_stock_report_supplier_can_be_null(): void
    {
        $this->createInventoryItem('Garam', 5.0, 2.0, 'kg', null);

        $service = app(ReportService::class);
        $result  = $service->getStockReport();

        $item = $result['items'][0];
        $this->assertNull($item['supplier']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Task 13.4 — HTTP endpoint tests for GET /api/admin/reports/stock
    // Validates: Requirement 15.3
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Admin can access the stock report endpoint and receives correct structure.
     * Validates: Requirement 15.3
     */
    #[Test]
    public function test_admin_can_access_stock_report_endpoint(): void
    {
        $this->createInventoryItem('Beras', 50.0, 10.0, 'kg', 'Supplier A');
        $this->createInventoryItem('Gula', 3.0, 10.0, 'kg', 'Supplier B');

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/reports/stock');

        $response->assertOk()
            ->assertJsonStructure([
                'message',
                'data' => [
                    'items' => [
                        '*' => [
                            'id',
                            'ingredient_name',
                            'unit',
                            'current_stock',
                            'min_stock',
                            'supplier',
                            'is_critical',
                        ],
                    ],
                    'total_items',
                    'critical_count',
                ],
            ]);
    }

    /**
     * Stock report endpoint returns correct is_critical flags and summary counts.
     * Validates: Requirement 15.3
     */
    #[Test]
    public function test_stock_report_endpoint_returns_correct_critical_flags_and_counts(): void
    {
        $this->createInventoryItem('Beras', 50.0, 10.0);   // normal
        $this->createInventoryItem('Gula', 3.0, 10.0);     // critical

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/reports/stock');

        $response->assertOk();

        $data = $response->json('data');
        $this->assertEquals(2, $data['total_items']);
        $this->assertEquals(1, $data['critical_count']);

        // Find items by name
        $items = collect($data['items'])->keyBy('ingredient_name');

        $this->assertFalse($items['Beras']['is_critical']);
        $this->assertTrue($items['Gula']['is_critical']);
    }

    /**
     * Non-admin (customer) cannot access the stock report endpoint.
     * Validates: Requirement 15.3
     */
    #[Test]
    public function test_customer_cannot_access_stock_report_endpoint(): void
    {
        $response = $this->actingAs($this->customer, 'sanctum')
            ->getJson('/api/admin/reports/stock');

        $response->assertForbidden();
    }

    /**
     * Unauthenticated request cannot access the stock report endpoint.
     * Validates: Requirement 15.3
     */
    #[Test]
    public function test_unauthenticated_cannot_access_stock_report_endpoint(): void
    {
        $response = $this->getJson('/api/admin/reports/stock');

        $response->assertUnauthorized();
    }

    /**
     * Stock report endpoint returns empty items when inventory is empty.
     * Validates: Requirement 15.3
     */
    #[Test]
    public function test_stock_report_endpoint_returns_empty_when_no_inventory(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/reports/stock');

        $response->assertOk();
        $this->assertCount(0, $response->json('data.items'));
        $this->assertEquals(0, $response->json('data.total_items'));
        $this->assertEquals(0, $response->json('data.critical_count'));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Task 13.5 — Export to Excel
    // Validates: Requirement 15.4
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Admin can export sales report as Excel and receives a downloadable .xlsx file.
     * Validates: Requirement 15.4
     */
    #[Test]
    public function test_admin_can_export_sales_report_as_excel(): void
    {
        Excel::fake();

        $this->createPaidOrder(50000, Carbon::parse('2025-01-10 10:00:00'));

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/admin/reports/export/sales', [
                'format' => 'excel',
                'period' => 'daily',
            ]);

        $response->assertOk();

        $expectedFilename = 'laporan-penjualan-' . now()->format('Y-m-d') . '.xlsx';
        Excel::assertDownloaded($expectedFilename);
    }

    /**
     * Admin can export stock report as Excel and receives a downloadable .xlsx file.
     * Validates: Requirement 15.4
     */
    #[Test]
    public function test_admin_can_export_stock_report_as_excel(): void
    {
        Excel::fake();

        $this->createInventoryItem('Beras', 50.0, 10.0, 'kg', 'Supplier A');
        $this->createInventoryItem('Gula', 3.0, 10.0, 'kg', 'Supplier B');

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/admin/reports/export/stock', [
                'format' => 'excel',
            ]);

        $response->assertOk();

        $expectedFilename = 'laporan-stok-' . now()->format('Y-m-d') . '.xlsx';
        Excel::assertDownloaded($expectedFilename);
    }

    /**
     * SalesReportExport uses the correct export class.
     * Validates: Requirement 15.4
     */
    #[Test]
    public function test_sales_export_uses_correct_export_class(): void
    {
        Excel::fake();

        $this->createPaidOrder(75000, Carbon::now());

        $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/admin/reports/export/sales', [
                'format' => 'excel',
                'period' => 'daily',
            ]);

        $expectedFilename = 'laporan-penjualan-' . now()->format('Y-m-d') . '.xlsx';
        Excel::assertDownloaded(
            $expectedFilename,
            fn (SalesReportExport $export) => $export instanceof SalesReportExport
        );
    }

    /**
     * StockReportExport uses the correct export class.
     * Validates: Requirement 15.4
     */
    #[Test]
    public function test_stock_export_uses_correct_export_class(): void
    {
        Excel::fake();

        $this->createInventoryItem('Tepung', 20.0, 5.0);

        $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/admin/reports/export/stock', [
                'format' => 'excel',
            ]);

        $expectedFilename = 'laporan-stok-' . now()->format('Y-m-d') . '.xlsx';
        Excel::assertDownloaded(
            $expectedFilename,
            fn (StockReportExport $export) => $export instanceof StockReportExport
        );
    }

    /**
     * Export sales endpoint rejects invalid format values.
     * Validates: Requirement 15.4
     */
    #[Test]
    public function test_export_sales_rejects_invalid_format(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/admin/reports/export/sales', [
                'format' => 'csv',
            ]);

        $response->assertUnprocessable();
    }

    /**
     * Export stock endpoint rejects invalid format values.
     * Validates: Requirement 15.4
     */
    #[Test]
    public function test_export_stock_rejects_invalid_format(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/admin/reports/export/stock', [
                'format' => 'csv',
            ]);

        $response->assertUnprocessable();
    }

    /**
     * Non-admin cannot access the export sales endpoint.
     * Validates: Requirement 15.4
     */
    #[Test]
    public function test_customer_cannot_export_sales_report(): void
    {
        $response = $this->actingAs($this->customer, 'sanctum')
            ->postJson('/api/admin/reports/export/sales', [
                'format' => 'excel',
            ]);

        $response->assertForbidden();
    }

    /**
     * Non-admin cannot access the export stock endpoint.
     * Validates: Requirement 15.4
     */
    #[Test]
    public function test_customer_cannot_export_stock_report(): void
    {
        $response = $this->actingAs($this->customer, 'sanctum')
            ->postJson('/api/admin/reports/export/stock', [
                'format' => 'excel',
            ]);

        $response->assertForbidden();
    }

    /**
     * SalesReportExport sheet data contains the correct period detail rows.
     * Validates: Requirement 15.4
     */
    #[Test]
    public function test_sales_report_export_contains_period_data(): void
    {
        $service = app(ReportService::class);

        $this->createPaidOrder(50000, Carbon::parse('2025-01-10 10:00:00'));
        $this->createPaidOrder(30000, Carbon::parse('2025-01-11 10:00:00'));

        $reportData = $service->getSalesReport('daily', '2025-01-10', '2025-01-11');
        $export     = new SalesReportExport($reportData);

        $sheets = $export->sheets();
        $this->assertCount(2, $sheets);

        // Detail sheet is index 1
        $detailSheet = $sheets[1];
        $this->assertEquals('Detail Periode', $detailSheet->title());

        $rows = $detailSheet->array();
        $this->assertCount(2, $rows); // 2 days of data

        // Headings
        $headings = $detailSheet->headings();
        $this->assertContains('Periode', $headings);
        $this->assertContains('Total Pendapatan', $headings);
        $this->assertContains('Jumlah Pesanan', $headings);
        $this->assertContains('Rata-rata Nilai Pesanan', $headings);
    }

    /**
     * StockReportExport array contains the correct number of data rows.
     * Validates: Requirement 15.4
     */
    #[Test]
    public function test_stock_report_export_contains_all_inventory_items(): void
    {
        $service = app(ReportService::class);

        $this->createInventoryItem('Beras', 50.0, 10.0, 'kg', 'Supplier A');
        $this->createInventoryItem('Gula', 3.0, 10.0, 'kg', 'Supplier B');
        $this->createInventoryItem('Tepung', 20.0, 5.0, 'kg', null);

        $reportData = $service->getStockReport();
        $export     = new StockReportExport($reportData);

        $rows = $export->array();

        // Rows = 6 header rows + 1 table heading + 3 data rows = 10
        $this->assertCount(10, $rows);

        // Table heading row (index 6, 0-based)
        $headingRow = $rows[6];
        $this->assertContains('Nama Bahan', $headingRow);
        $this->assertContains('Satuan', $headingRow);
        $this->assertContains('Stok Saat Ini', $headingRow);
        $this->assertContains('Stok Minimal', $headingRow);
        $this->assertContains('Supplier', $headingRow);
        $this->assertContains('Status', $headingRow);
    }

    /**
     * StockReportExport marks critical items with the KRITIS status label.
     * Validates: Requirement 15.4
     */
    #[Test]
    public function test_stock_report_export_marks_critical_items(): void
    {
        $service = app(ReportService::class);

        $this->createInventoryItem('Beras', 50.0, 10.0);  // normal
        $this->createInventoryItem('Gula', 3.0, 10.0);    // critical

        $reportData = $service->getStockReport();
        $export     = new StockReportExport($reportData);

        $rows = $export->array();

        // Data rows start at index 7 (after 6 header rows + 1 heading row)
        $dataRows = array_slice($rows, 7);

        // Find rows by ingredient name (sorted alphabetically: Beras, Gula)
        $berasRow = $dataRows[0]; // Beras — normal
        $gulaRow  = $dataRows[1]; // Gula  — critical

        // Status is the last column (index 5)
        $this->assertEquals('Normal', $berasRow[5]);
        $this->assertStringContainsString('KRITIS', $gulaRow[5]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Task 13.6 — Export to PDF (barryvdh/laravel-dompdf)
    // Validates: Requirement 15.4
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Admin can export sales report as PDF and receives a downloadable .pdf file.
     * Validates: Requirement 15.4
     */
    #[Test]
    public function test_admin_can_export_sales_report_as_pdf(): void
    {
        $this->createPaidOrder(50000, Carbon::parse('2025-01-10 10:00:00'));

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/admin/reports/export/sales', [
                'format' => 'pdf',
                'period' => 'daily',
            ]);

        $response->assertOk();
        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));
        $this->assertStringContainsString(
            'laporan-penjualan-' . now()->format('Y-m-d') . '.pdf',
            $response->headers->get('Content-Disposition')
        );
    }

    /**
     * PDF sales export response body is non-empty (contains actual PDF bytes).
     * Validates: Requirement 15.4
     */
    #[Test]
    public function test_sales_pdf_export_response_is_non_empty(): void
    {
        $this->createPaidOrder(75000, Carbon::parse('2025-06-01 10:00:00'));

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/admin/reports/export/sales', [
                'format'    => 'pdf',
                'period'    => 'daily',
                'date_from' => '2025-06-01',
                'date_to'   => '2025-06-01',
            ]);

        $response->assertOk();
        $this->assertNotEmpty($response->getContent());
        // PDF files start with the %PDF magic bytes
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    /**
     * Admin can export stock report as PDF and receives a downloadable .pdf file.
     * Validates: Requirement 15.4
     */
    #[Test]
    public function test_admin_can_export_stock_report_as_pdf(): void
    {
        $this->createInventoryItem('Beras', 50.0, 10.0, 'kg', 'Supplier A');
        $this->createInventoryItem('Gula', 3.0, 10.0, 'kg', 'Supplier B');

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/admin/reports/export/stock', [
                'format' => 'pdf',
            ]);

        $response->assertOk();
        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));
        $this->assertStringContainsString(
            'laporan-stok-' . now()->format('Y-m-d') . '.pdf',
            $response->headers->get('Content-Disposition')
        );
    }

    /**
     * PDF stock export response body is non-empty (contains actual PDF bytes).
     * Validates: Requirement 15.4
     */
    #[Test]
    public function test_stock_pdf_export_response_is_non_empty(): void
    {
        $this->createInventoryItem('Tepung', 20.0, 5.0, 'kg', null);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/admin/reports/export/stock', [
                'format' => 'pdf',
            ]);

        $response->assertOk();
        $this->assertNotEmpty($response->getContent());
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    /**
     * PDF sales export works with all three period types.
     * Validates: Requirement 15.4
     */
    #[Test]
    public function test_sales_pdf_export_works_for_all_periods(): void
    {
        $this->createPaidOrder(50000, Carbon::now());

        foreach (['daily', 'weekly', 'monthly'] as $period) {
            $response = $this->actingAs($this->admin, 'sanctum')
                ->postJson('/api/admin/reports/export/sales', [
                    'format' => 'pdf',
                    'period' => $period,
                ]);

            $response->assertOk();
            $this->assertEquals(
                'application/pdf',
                $response->headers->get('Content-Type'),
                "PDF export failed for period: {$period}"
            );
        }
    }

    /**
     * Non-admin cannot export sales report as PDF.
     * Validates: Requirement 15.4
     */
    #[Test]
    public function test_customer_cannot_export_sales_report_as_pdf(): void
    {
        $response = $this->actingAs($this->customer, 'sanctum')
            ->postJson('/api/admin/reports/export/sales', [
                'format' => 'pdf',
            ]);

        $response->assertForbidden();
    }

    /**
     * Non-admin cannot export stock report as PDF.
     * Validates: Requirement 15.4
     */
    #[Test]
    public function test_customer_cannot_export_stock_report_as_pdf(): void
    {
        $response = $this->actingAs($this->customer, 'sanctum')
            ->postJson('/api/admin/reports/export/stock', [
                'format' => 'pdf',
            ]);

        $response->assertForbidden();
    }

    /**
     * PDF sales export works with an empty dataset (no orders in range).
     * Validates: Requirement 15.4
     */
    #[Test]
    public function test_sales_pdf_export_works_with_empty_data(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/admin/reports/export/sales', [
                'format'    => 'pdf',
                'period'    => 'daily',
                'date_from' => '2020-01-01',
                'date_to'   => '2020-01-31',
            ]);

        $response->assertOk();
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    /**
     * PDF stock export works with an empty inventory.
     * Validates: Requirement 15.4
     */
    #[Test]
    public function test_stock_pdf_export_works_with_empty_inventory(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/admin/reports/export/stock', [
                'format' => 'pdf',
            ]);

        $response->assertOk();
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Task 13.8 — Hourly revenue chart data
    // Validates: Requirement 15.5
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * getHourlyRevenue() always returns exactly 24 entries (one per hour 0–23).
     * Validates: Requirement 15.5
     */
    #[Test]
    public function test_hourly_revenue_returns_exactly_24_entries(): void
    {
        $service = app(ReportService::class);
        $result  = $service->getHourlyRevenue('2025-01-10');

        $this->assertCount(24, $result);
    }

    /**
     * Each entry contains 'hour' (0–23) and 'revenue' keys.
     * Validates: Requirement 15.5
     */
    #[Test]
    public function test_hourly_revenue_entries_contain_required_keys(): void
    {
        $service = app(ReportService::class);
        $result  = $service->getHourlyRevenue('2025-01-10');

        foreach ($result as $index => $entry) {
            $this->assertArrayHasKey('hour', $entry, "Entry {$index} missing 'hour' key");
            $this->assertArrayHasKey('revenue', $entry, "Entry {$index} missing 'revenue' key");
        }
    }

    /**
     * Hours are numbered sequentially from 0 to 23.
     * Validates: Requirement 15.5
     */
    #[Test]
    public function test_hourly_revenue_hours_are_sequential_0_to_23(): void
    {
        $service = app(ReportService::class);
        $result  = $service->getHourlyRevenue('2025-01-10');

        for ($h = 0; $h < 24; $h++) {
            $this->assertEquals($h, $result[$h]['hour'], "Expected hour {$h} at index {$h}");
        }
    }

    /**
     * Hours with no orders have revenue of 0.0.
     * Validates: Requirement 15.5
     */
    #[Test]
    public function test_hourly_revenue_empty_hours_have_zero_revenue(): void
    {
        $service = app(ReportService::class);
        $result  = $service->getHourlyRevenue('2020-01-01'); // date with no orders

        foreach ($result as $entry) {
            $this->assertEquals(0.0, $entry['revenue'], "Hour {$entry['hour']} should have 0.0 revenue");
        }
    }

    /**
     * Revenue is correctly aggregated for the hour of the order.
     * Validates: Requirement 15.5
     */
    #[Test]
    public function test_hourly_revenue_aggregates_revenue_by_hour(): void
    {
        // Two paid orders at 14:xx on 2025-01-10
        $this->createPaidOrder(50000, Carbon::parse('2025-01-10 14:15:00'));
        $this->createPaidOrder(30000, Carbon::parse('2025-01-10 14:45:00'));

        // One paid order at 09:xx on 2025-01-10
        $this->createPaidOrder(20000, Carbon::parse('2025-01-10 09:30:00'));

        $service = app(ReportService::class);
        $result  = $service->getHourlyRevenue('2025-01-10');

        // Hour 14 should have 80000
        $this->assertEquals(80000.0, $result[14]['revenue']);

        // Hour 9 should have 20000
        $this->assertEquals(20000.0, $result[9]['revenue']);

        // Other hours should be 0
        $this->assertEquals(0.0, $result[0]['revenue']);
        $this->assertEquals(0.0, $result[23]['revenue']);
    }

    /**
     * Only paid orders are counted in hourly revenue.
     * Validates: Requirement 15.5
     */
    #[Test]
    public function test_hourly_revenue_only_counts_paid_orders(): void
    {
        // Paid order at hour 10
        $this->createPaidOrder(50000, Carbon::parse('2025-01-10 10:00:00'));

        // Unpaid (pending) order at hour 10 — should be excluded
        $unpaid = Order::create([
            'user_id'        => $this->customer->id,
            'total_price'    => 99999,
            'payment_status' => 'pending',
            'order_status'   => 'Diterima',
            'order_type'     => 'dine_in',
        ]);
        $unpaid->created_at = Carbon::parse('2025-01-10 10:30:00');
        $unpaid->save();

        $service = app(ReportService::class);
        $result  = $service->getHourlyRevenue('2025-01-10');

        // Only the paid order's revenue should appear
        $this->assertEquals(50000.0, $result[10]['revenue']);
    }

    /**
     * Orders from other dates are not included in the hourly revenue for the target date.
     * Validates: Requirement 15.5
     */
    #[Test]
    public function test_hourly_revenue_excludes_orders_from_other_dates(): void
    {
        // Order on target date
        $this->createPaidOrder(50000, Carbon::parse('2025-01-10 12:00:00'));

        // Order on a different date — should be excluded
        $this->createPaidOrder(99999, Carbon::parse('2025-01-11 12:00:00'));

        $service = app(ReportService::class);
        $result  = $service->getHourlyRevenue('2025-01-10');

        $this->assertEquals(50000.0, $result[12]['revenue']);
        // Total revenue across all hours should only be 50000
        $totalRevenue = array_sum(array_column($result, 'revenue'));
        $this->assertEquals(50000.0, $totalRevenue);
    }

    /**
     * getHourlyRevenue() defaults to today when no date is provided.
     * Validates: Requirement 15.5
     */
    #[Test]
    public function test_hourly_revenue_defaults_to_today(): void
    {
        // Create a paid order for today
        $this->createPaidOrder(75000, Carbon::now());

        $service = app(ReportService::class);
        $result  = $service->getHourlyRevenue(); // no date argument

        $this->assertCount(24, $result);

        // Total revenue should include today's order
        $totalRevenue = array_sum(array_column($result, 'revenue'));
        $this->assertEquals(75000.0, $totalRevenue);
    }

    /**
     * Revenue values are returned as floats.
     * Validates: Requirement 15.5
     */
    #[Test]
    public function test_hourly_revenue_values_are_floats(): void
    {
        $this->createPaidOrder(50000, Carbon::parse('2025-01-10 08:00:00'));

        $service = app(ReportService::class);
        $result  = $service->getHourlyRevenue('2025-01-10');

        foreach ($result as $entry) {
            $this->assertIsFloat($entry['revenue'], "Revenue for hour {$entry['hour']} should be a float");
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Task 13.8 — HTTP endpoint tests for GET /api/admin/reports/hourly-revenue
    // Validates: Requirement 15.5
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Admin can access the hourly revenue endpoint and receives correct structure.
     * Validates: Requirement 15.5
     */
    #[Test]
    public function test_admin_can_access_hourly_revenue_endpoint(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/reports/hourly-revenue');

        $response->assertOk()
            ->assertJsonStructure([
                'message',
                'data' => [
                    '*' => ['hour', 'revenue'],
                ],
            ]);

        $this->assertCount(24, $response->json('data'));
    }

    /**
     * Hourly revenue endpoint accepts an optional date query parameter.
     * Validates: Requirement 15.5
     */
    #[Test]
    public function test_hourly_revenue_endpoint_accepts_date_parameter(): void
    {
        $this->createPaidOrder(60000, Carbon::parse('2025-03-15 11:00:00'));

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/reports/hourly-revenue?date=2025-03-15');

        $response->assertOk();
        $this->assertCount(24, $response->json('data'));

        // Hour 11 should have 60000
        $data   = $response->json('data');
        $hour11 = collect($data)->firstWhere('hour', 11);
        $this->assertNotNull($hour11);
        $this->assertEquals(60000.0, $hour11['revenue']);
    }

    /**
     * Hourly revenue endpoint returns 24 zero-revenue entries for a date with no orders.
     * Validates: Requirement 15.5
     */
    #[Test]
    public function test_hourly_revenue_endpoint_returns_zeros_for_empty_date(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/reports/hourly-revenue?date=2020-01-01');

        $response->assertOk();

        $data = $response->json('data');
        $this->assertCount(24, $data);

        foreach ($data as $entry) {
            $this->assertEquals(0.0, $entry['revenue']);
        }
    }

    /**
     * Hourly revenue endpoint rejects an invalid date format.
     * Validates: Requirement 15.5
     */
    #[Test]
    public function test_hourly_revenue_endpoint_rejects_invalid_date(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/reports/hourly-revenue?date=not-a-date');

        $response->assertUnprocessable();
    }

    /**
     * Non-admin (customer) cannot access the hourly revenue endpoint.
     * Validates: Requirement 15.5
     */
    #[Test]
    public function test_customer_cannot_access_hourly_revenue_endpoint(): void
    {
        $response = $this->actingAs($this->customer, 'sanctum')
            ->getJson('/api/admin/reports/hourly-revenue');

        $response->assertForbidden();
    }

    /**
     * Unauthenticated request cannot access the hourly revenue endpoint.
     * Validates: Requirement 15.5
     */
    #[Test]
    public function test_unauthenticated_cannot_access_hourly_revenue_endpoint(): void
    {
        $response = $this->getJson('/api/admin/reports/hourly-revenue');

        $response->assertUnauthorized();
    }

    /**
     * Hourly revenue endpoint only counts paid orders (not pending/failed).
     * Validates: Requirement 15.5
     */
    #[Test]
    public function test_hourly_revenue_endpoint_only_counts_paid_orders(): void
    {
        // Paid order at hour 15
        $this->createPaidOrder(45000, Carbon::parse('2025-05-20 15:00:00'));

        // Pending order at hour 15 — should be excluded
        $pending = Order::create([
            'user_id'        => $this->customer->id,
            'total_price'    => 99999,
            'payment_status' => 'pending',
            'order_status'   => 'Diterima',
            'order_type'     => 'dine_in',
        ]);
        $pending->created_at = Carbon::parse('2025-05-20 15:30:00');
        $pending->save();

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/reports/hourly-revenue?date=2025-05-20');

        $response->assertOk();

        $data   = $response->json('data');
        $hour15 = collect($data)->firstWhere('hour', 15);
        $this->assertEquals(45000.0, $hour15['revenue']);
    }

    /**
     * Hourly revenue endpoint response message is correct.
     * Validates: Requirement 15.5
     */
    #[Test]
    public function test_hourly_revenue_endpoint_returns_correct_message(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/reports/hourly-revenue');

        $response->assertOk()
            ->assertJson(['message' => 'Data pendapatan per jam berhasil diambil.']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Task 13.9 — Dashboard metrics endpoint
    // Validates: Requirement 15.6
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Helper: create an inventory item (reused from stock report tests).
     * Already defined above as createInventoryItem().
     */

    /**
     * getDashboardMetrics() returns all four required keys.
     * Validates: Requirement 15.6
     */
    #[Test]
    public function test_dashboard_metrics_returns_required_keys(): void
    {
        $service = app(ReportService::class);
        $result  = $service->getDashboardMetrics();

        $this->assertArrayHasKey('today_revenue', $result);
        $this->assertArrayHasKey('today_orders', $result);
        $this->assertArrayHasKey('today_customers', $result);
        $this->assertArrayHasKey('critical_stock_count', $result);
    }

    /**
     * today_revenue only counts paid orders from today.
     * Validates: Requirement 15.6
     */
    #[Test]
    public function test_dashboard_metrics_today_revenue_only_counts_paid_orders_from_today(): void
    {
        // Paid order today
        $this->createPaidOrder(75000, Carbon::now());

        // Unpaid (pending) order today — should NOT be counted in revenue
        $pending = Order::create([
            'user_id'        => $this->customer->id,
            'total_price'    => 50000,
            'payment_status' => 'pending',
            'order_status'   => 'Diterima',
            'order_type'     => 'dine_in',
        ]);
        $pending->created_at = Carbon::now();
        $pending->save();

        // Paid order from yesterday — should NOT be counted
        $this->createPaidOrder(100000, Carbon::yesterday());

        $service = app(ReportService::class);
        $result  = $service->getDashboardMetrics();

        $this->assertEquals(75000.0, $result['today_revenue']);
    }

    /**
     * today_orders counts only paid orders from today.
     * Validates: Requirement 15.6
     */
    #[Test]
    public function test_dashboard_metrics_today_orders_counts_only_paid_orders_from_today(): void
    {
        // 2 paid orders today
        $this->createPaidOrder(50000, Carbon::now());
        $this->createPaidOrder(30000, Carbon::now());

        // 1 pending order today — should NOT be counted
        $pending = Order::create([
            'user_id'        => $this->customer->id,
            'total_price'    => 20000,
            'payment_status' => 'pending',
            'order_status'   => 'Diterima',
            'order_type'     => 'dine_in',
        ]);
        $pending->created_at = Carbon::now();
        $pending->save();

        // 1 paid order yesterday — should NOT be counted
        $this->createPaidOrder(40000, Carbon::yesterday());

        $service = app(ReportService::class);
        $result  = $service->getDashboardMetrics();

        $this->assertEquals(2, $result['today_orders']);
    }

    /**
     * today_customers counts unique customers (distinct user_id) who ordered today.
     * Validates: Requirement 15.6
     */
    #[Test]
    public function test_dashboard_metrics_today_customers_counts_unique_customers(): void
    {
        // Create a second customer
        $customer2 = User::factory()->create(['role' => 'customer']);
        $customer2->assignRole('customer');

        // customer1 places 2 orders today
        Order::create([
            'user_id'        => $this->customer->id,
            'total_price'    => 50000,
            'payment_status' => 'paid',
            'order_status'   => 'Disajikan',
            'order_type'     => 'dine_in',
        ])->update(['created_at' => Carbon::now()]);

        Order::create([
            'user_id'        => $this->customer->id,
            'total_price'    => 30000,
            'payment_status' => 'paid',
            'order_status'   => 'Disajikan',
            'order_type'     => 'dine_in',
        ])->update(['created_at' => Carbon::now()]);

        // customer2 places 1 order today
        Order::create([
            'user_id'        => $customer2->id,
            'total_price'    => 40000,
            'payment_status' => 'paid',
            'order_status'   => 'Disajikan',
            'order_type'     => 'dine_in',
        ])->update(['created_at' => Carbon::now()]);

        $service = app(ReportService::class);
        $result  = $service->getDashboardMetrics();

        // Should count 2 unique customers, not 3 orders
        $this->assertEquals(2, $result['today_customers']);
    }

    /**
     * today_customers excludes orders with null user_id (guest/anonymous orders).
     * Validates: Requirement 15.6
     */
    #[Test]
    public function test_dashboard_metrics_today_customers_excludes_null_user_id(): void
    {
        // Order with a real customer
        Order::create([
            'user_id'        => $this->customer->id,
            'total_price'    => 50000,
            'payment_status' => 'paid',
            'order_status'   => 'Disajikan',
            'order_type'     => 'dine_in',
        ])->update(['created_at' => Carbon::now()]);

        // Guest order (no user_id) — should NOT be counted
        Order::create([
            'user_id'        => null,
            'total_price'    => 30000,
            'payment_status' => 'paid',
            'order_status'   => 'Disajikan',
            'order_type'     => 'dine_in',
        ])->update(['created_at' => Carbon::now()]);

        $service = app(ReportService::class);
        $result  = $service->getDashboardMetrics();

        $this->assertEquals(1, $result['today_customers']);
    }

    /**
     * today_customers excludes orders from previous days.
     * Validates: Requirement 15.6
     */
    #[Test]
    public function test_dashboard_metrics_today_customers_excludes_previous_days(): void
    {
        // Order today
        Order::create([
            'user_id'        => $this->customer->id,
            'total_price'    => 50000,
            'payment_status' => 'paid',
            'order_status'   => 'Disajikan',
            'order_type'     => 'dine_in',
        ])->update(['created_at' => Carbon::now()]);

        // Order yesterday — should NOT be counted
        $order = Order::create([
            'user_id'        => $this->customer->id,
            'total_price'    => 30000,
            'payment_status' => 'paid',
            'order_status'   => 'Disajikan',
            'order_type'     => 'dine_in',
        ]);
        $order->created_at = Carbon::yesterday();
        $order->save();

        $service = app(ReportService::class);
        $result  = $service->getDashboardMetrics();

        $this->assertEquals(1, $result['today_customers']);
    }

    /**
     * critical_stock_count counts inventory items where current_stock ≤ min_stock.
     * Validates: Requirement 15.6
     */
    #[Test]
    public function test_dashboard_metrics_critical_stock_count_is_correct(): void
    {
        // 2 normal items
        $this->createInventoryItem('Beras', 50.0, 10.0);   // normal (50 > 10)
        $this->createInventoryItem('Tepung', 20.0, 5.0);   // normal (20 > 5)

        // 2 critical items
        $this->createInventoryItem('Gula', 3.0, 10.0);     // critical (3 < 10)
        $this->createInventoryItem('Minyak', 5.0, 5.0);    // critical (5 == 5)

        $service = app(ReportService::class);
        $result  = $service->getDashboardMetrics();

        $this->assertEquals(2, $result['critical_stock_count']);
    }

    /**
     * critical_stock_count is 0 when no inventory items are critical.
     * Validates: Requirement 15.6
     */
    #[Test]
    public function test_dashboard_metrics_critical_stock_count_zero_when_no_critical_items(): void
    {
        $this->createInventoryItem('Beras', 50.0, 10.0);
        $this->createInventoryItem('Tepung', 20.0, 5.0);

        $service = app(ReportService::class);
        $result  = $service->getDashboardMetrics();

        $this->assertEquals(0, $result['critical_stock_count']);
    }

    /**
     * All metrics return zero when there are no orders or inventory today.
     * Validates: Requirement 15.6
     */
    #[Test]
    public function test_dashboard_metrics_all_zero_when_no_data(): void
    {
        $service = app(ReportService::class);
        $result  = $service->getDashboardMetrics();

        $this->assertEquals(0.0, $result['today_revenue']);
        $this->assertEquals(0, $result['today_orders']);
        $this->assertEquals(0, $result['today_customers']);
        $this->assertEquals(0, $result['critical_stock_count']);
    }

    /**
     * today_revenue is returned as a float.
     * Validates: Requirement 15.6
     */
    #[Test]
    public function test_dashboard_metrics_today_revenue_is_float(): void
    {
        $this->createPaidOrder(50000, Carbon::now());

        $service = app(ReportService::class);
        $result  = $service->getDashboardMetrics();

        $this->assertIsFloat($result['today_revenue']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Task 13.9 — HTTP endpoint tests for GET /api/admin/reports/dashboard
    // Validates: Requirement 15.6
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Admin can access the dashboard metrics endpoint and receives correct structure.
     * Validates: Requirement 15.6
     */
    #[Test]
    public function test_admin_can_access_dashboard_metrics_endpoint(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/reports/dashboard');

        $response->assertOk()
            ->assertJsonStructure([
                'message',
                'data' => [
                    'today_revenue',
                    'today_orders',
                    'today_customers',
                    'critical_stock_count',
                ],
            ]);
    }

    /**
     * Dashboard metrics endpoint returns correct values for today's paid orders.
     * Validates: Requirement 15.6
     */
    #[Test]
    public function test_dashboard_metrics_endpoint_returns_correct_values(): void
    {
        // 2 paid orders today
        $this->createPaidOrder(60000, Carbon::now());
        $this->createPaidOrder(40000, Carbon::now());

        // 1 critical inventory item
        $this->createInventoryItem('Gula', 2.0, 10.0);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/reports/dashboard');

        $response->assertOk();

        $data = $response->json('data');
        $this->assertEquals(100000.0, $data['today_revenue']);
        $this->assertEquals(2, $data['today_orders']);
        $this->assertEquals(1, $data['today_customers']); // same customer placed both orders
        $this->assertEquals(1, $data['critical_stock_count']);
    }

    /**
     * Dashboard metrics endpoint returns correct message.
     * Validates: Requirement 15.6
     */
    #[Test]
    public function test_dashboard_metrics_endpoint_returns_correct_message(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/reports/dashboard');

        $response->assertOk()
            ->assertJson(['message' => 'Metrik dashboard berhasil diambil.']);
    }

    /**
     * Non-admin (customer) cannot access the dashboard metrics endpoint.
     * Validates: Requirement 15.6
     */
    #[Test]
    public function test_customer_cannot_access_dashboard_metrics_endpoint(): void
    {
        $response = $this->actingAs($this->customer, 'sanctum')
            ->getJson('/api/admin/reports/dashboard');

        $response->assertForbidden();
    }

    /**
     * Unauthenticated request cannot access the dashboard metrics endpoint.
     * Validates: Requirement 15.6
     */
    #[Test]
    public function test_unauthenticated_cannot_access_dashboard_metrics_endpoint(): void
    {
        $response = $this->getJson('/api/admin/reports/dashboard');

        $response->assertUnauthorized();
    }

    /**
     * Dashboard metrics endpoint returns zeros when there are no orders or inventory today.
     * Validates: Requirement 15.6
     */
    #[Test]
    public function test_dashboard_metrics_endpoint_returns_zeros_when_no_data(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/reports/dashboard');

        $response->assertOk();

        $data = $response->json('data');
        $this->assertEquals(0.0, $data['today_revenue']);
        $this->assertEquals(0, $data['today_orders']);
        $this->assertEquals(0, $data['today_customers']);
        $this->assertEquals(0, $data['critical_stock_count']);
    }
}