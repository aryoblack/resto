<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;

use App\Jobs\ExportReportJob;
use App\Mail\ReportExportReady;
use App\Models\Category;
use App\Models\Inventory;
use App\Models\Menu;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Feature tests for Task 13.7 — Queue job for large report exports with email notification.
 *
 * Covers:
 *   - POST /api/admin/reports/export/sales with async=true returns 202 and dispatches ExportReportJob
 *   - POST /api/admin/reports/export/stock with async=true returns 202 and dispatches ExportReportJob
 *   - Job is dispatched with correct parameters (type, format, period, dateFrom, dateTo, userEmail)
 *   - Non-admin cannot trigger async export
 *   - Unauthenticated request is rejected
 *   - ExportReportJob generates the file and sends email notification
 *   - ReportExportReady mailable has correct subject and attachment
 *
 * Validates: Requirement 15.4
 */
class AsyncExportReportTest extends TestCase
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

        $this->admin = User::factory()->create([
            'role'  => 'admin',
            'email' => 'admin@resto.test',
        ]);
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
    // HTTP endpoint — async=true returns 202 and dispatches job
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * POST /api/admin/reports/export/sales with async=true returns 202 Accepted.
     * Validates: Requirement 15.4
     */
    #[Test]
    public function test_async_sales_export_returns_202(): void
    {
        Queue::fake();

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/admin/reports/export/sales', [
                'format' => 'excel',
                'period' => 'daily',
                'async'  => true,
            ]);

        $response->assertStatus(202)
            ->assertJsonStructure(['message'])
            ->assertJsonFragment(['message' => 'Ekspor laporan sedang diproses. Anda akan menerima email saat selesai.']);
    }

    /**
     * POST /api/admin/reports/export/stock with async=true returns 202 Accepted.
     * Validates: Requirement 15.4
     */
    #[Test]
    public function test_async_stock_export_returns_202(): void
    {
        Queue::fake();

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/admin/reports/export/stock', [
                'format' => 'excel',
                'async'  => true,
            ]);

        $response->assertStatus(202)
            ->assertJsonStructure(['message'])
            ->assertJsonFragment(['message' => 'Ekspor laporan stok sedang diproses. Anda akan menerima email saat selesai.']);
    }

    /**
     * Async sales export dispatches ExportReportJob with correct parameters.
     * Validates: Requirement 15.4
     */
    #[Test]
    public function test_async_sales_export_dispatches_job_with_correct_params(): void
    {
        Queue::fake();

        $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/admin/reports/export/sales', [
                'format'    => 'excel',
                'period'    => 'monthly',
                'date_from' => '2025-01-01',
                'date_to'   => '2025-01-31',
                'async'     => true,
            ]);

        Queue::assertPushed(ExportReportJob::class, function (ExportReportJob $job) {
            return $job->type      === 'sales'
                && $job->format    === 'excel'
                && $job->period    === 'monthly'
                && $job->dateFrom  === '2025-01-01'
                && $job->dateTo    === '2025-01-31'
                && $job->userEmail === 'admin@resto.test';
        });
    }

    /**
     * Async stock export dispatches ExportReportJob with correct parameters.
     * Validates: Requirement 15.4
     */
    #[Test]
    public function test_async_stock_export_dispatches_job_with_correct_params(): void
    {
        Queue::fake();

        $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/admin/reports/export/stock', [
                'format' => 'pdf',
                'async'  => true,
            ]);

        Queue::assertPushed(ExportReportJob::class, function (ExportReportJob $job) {
            return $job->type      === 'stock'
                && $job->format    === 'pdf'
                && $job->period    === null
                && $job->dateFrom  === null
                && $job->dateTo    === null
                && $job->userEmail === 'admin@resto.test';
        });
    }

    /**
     * Exactly one ExportReportJob is dispatched per async export request.
     */
    #[Test]
    public function test_async_export_dispatches_exactly_one_job(): void
    {
        Queue::fake();

        $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/admin/reports/export/sales', [
                'format' => 'excel',
                'async'  => true,
            ]);

        Queue::assertPushed(ExportReportJob::class, 1);
    }

    /**
     * Synchronous export (async=false) does NOT dispatch a job.
     */
    #[Test]
    public function test_sync_export_does_not_dispatch_job(): void
    {
        Queue::fake();

        // Sync export — should return a file download, not 202
        $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/admin/reports/export/stock', [
                'format' => 'excel',
                'async'  => false,
            ]);

        Queue::assertNotPushed(ExportReportJob::class);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Authorization
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Non-admin (customer) cannot trigger async export.
     */
    #[Test]
    public function test_customer_cannot_trigger_async_export(): void
    {
        Queue::fake();

        $response = $this->actingAs($this->customer, 'sanctum')
            ->postJson('/api/admin/reports/export/sales', [
                'format' => 'excel',
                'async'  => true,
            ]);

        $response->assertForbidden();
        Queue::assertNotPushed(ExportReportJob::class);
    }

    /**
     * Unauthenticated request is rejected with 401.
     */
    #[Test]
    public function test_unauthenticated_cannot_trigger_async_export(): void
    {
        Queue::fake();

        $response = $this->postJson('/api/admin/reports/export/sales', [
            'format' => 'excel',
            'async'  => true,
        ]);

        $response->assertUnauthorized();
        Queue::assertNotPushed(ExportReportJob::class);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ExportReportJob execution — generates file and sends email
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * ExportReportJob for sales Excel generates a file and sends email notification.
     * Validates: Requirement 15.4
     */
    #[Test]
    public function test_export_job_generates_sales_excel_and_sends_email(): void
    {
        Mail::fake();
        Storage::fake('local');

        // Create some paid orders so the report has data
        $this->createPaidOrder(50000, Carbon::parse('2025-01-10 10:00:00'));

        $job = new ExportReportJob(
            type:      'sales',
            format:    'excel',
            period:    'daily',
            dateFrom:  '2025-01-01',
            dateTo:    '2025-01-31',
            userEmail: 'admin@resto.test',
        );

        $job->handle(app(\App\Services\ReportService::class));

        // Verify a file was stored in the exports directory
        $files = Storage::disk('local')->files('exports');
        $this->assertNotEmpty($files, 'Expected at least one file in exports/');

        $exportedFile = $files[0];
        $this->assertStringEndsWith('.xlsx', $exportedFile);

        // Verify email was sent
        Mail::assertSent(ReportExportReady::class, function (ReportExportReady $mail) {
            return $mail->hasTo('admin@resto.test')
                && $mail->type   === 'sales'
                && $mail->format === 'excel';
        });
    }

    /**
     * ExportReportJob for stock Excel generates a file and sends email notification.
     * Validates: Requirement 15.4
     */
    #[Test]
    public function test_export_job_generates_stock_excel_and_sends_email(): void
    {
        Mail::fake();
        Storage::fake('local');

        // Create some inventory items
        Inventory::create([
            'ingredient_name' => 'Beras',
            'unit'            => 'kg',
            'current_stock'   => 50,
            'min_stock'       => 10,
        ]);

        $job = new ExportReportJob(
            type:      'stock',
            format:    'excel',
            period:    null,
            dateFrom:  null,
            dateTo:    null,
            userEmail: 'admin@resto.test',
        );

        $job->handle(app(\App\Services\ReportService::class));

        // Verify a file was stored
        $files = Storage::disk('local')->files('exports');
        $this->assertNotEmpty($files);
        $this->assertStringEndsWith('.xlsx', $files[0]);

        // Verify email was sent
        Mail::assertSent(ReportExportReady::class, function (ReportExportReady $mail) {
            return $mail->hasTo('admin@resto.test')
                && $mail->type   === 'stock'
                && $mail->format === 'excel';
        });
    }

    /**
     * ExportReportJob sends email to the correct recipient.
     */
    #[Test]
    public function test_export_job_sends_email_to_correct_recipient(): void
    {
        Mail::fake();
        Storage::fake('local');

        $job = new ExportReportJob(
            type:      'stock',
            format:    'excel',
            period:    null,
            dateFrom:  null,
            dateTo:    null,
            userEmail: 'manager@resto.test',
        );

        $job->handle(app(\App\Services\ReportService::class));

        Mail::assertSent(ReportExportReady::class, function (ReportExportReady $mail) {
            return $mail->hasTo('manager@resto.test');
        });

        Mail::assertNotSent(ReportExportReady::class, function (ReportExportReady $mail) {
            return $mail->hasTo('admin@resto.test');
        });
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ReportExportReady mailable
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * ReportExportReady mailable has correct subject for sales report.
     */
    #[Test]
    public function test_report_export_ready_mailable_subject_for_sales(): void
    {
        $mailable = new ReportExportReady(
            type:     'sales',
            format:   'excel',
            filename: 'laporan-penjualan-2025-01-15.xlsx',
            filePath: 'exports/laporan-penjualan-2025-01-15.xlsx',
        );

        $mailable->assertHasSubject('Laporan Penjualan Siap Diunduh');
    }

    /**
     * ReportExportReady mailable has correct subject for stock report.
     */
    #[Test]
    public function test_report_export_ready_mailable_subject_for_stock(): void
    {
        $mailable = new ReportExportReady(
            type:     'stock',
            format:   'pdf',
            filename: 'laporan-stok-2025-01-15.pdf',
            filePath: 'exports/laporan-stok-2025-01-15.pdf',
        );

        $mailable->assertHasSubject('Laporan Stok Siap Diunduh');
    }

    /**
     * ReportExportReady mailable renders the correct view.
     */
    #[Test]
    public function test_report_export_ready_mailable_renders_view(): void
    {
        $mailable = new ReportExportReady(
            type:     'sales',
            format:   'excel',
            filename: 'laporan-penjualan-2025-01-15.xlsx',
            filePath: 'exports/laporan-penjualan-2025-01-15.xlsx',
        );

        $mailable->assertSeeInHtml('Laporan Siap Diunduh');
        $mailable->assertSeeInHtml('laporan-penjualan-2025-01-15.xlsx');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helper
    // ─────────────────────────────────────────────────────────────────────────

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

        $order->created_at = $at;
        $order->save();

        return $order;
    }
}