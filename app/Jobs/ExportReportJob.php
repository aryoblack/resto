<?php

namespace App\Jobs;

use App\Exports\SalesReportExport;
use App\Exports\StockReportExport;
use App\Mail\ReportExportReady;
use App\Services\ReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

/**
 * ExportReportJob — queued job that generates a report file and emails the result.
 *
 * Dispatched by ReportController when `async=true` is passed to the export endpoints.
 * Supports both sales and stock reports in Excel (.xlsx) or PDF format.
 * On completion, the generated file is saved to `storage/app/exports/` and an email
 * notification is sent to the requesting user with the file attached.
 *
 * Validates: Requirement 15.4
 */
class ExportReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * Timeout in seconds for the job.
     */
    public int $timeout = 120;

    /**
     * Create a new job instance.
     *
     * @param  string       $type       'sales' | 'stock'
     * @param  string       $format     'excel' | 'pdf'
     * @param  string|null  $period     'daily' | 'weekly' | 'monthly' (sales only)
     * @param  string|null  $dateFrom   Y-m-d (sales only)
     * @param  string|null  $dateTo     Y-m-d (sales only)
     * @param  string       $userEmail  Recipient email address
     */
    public function __construct(
        public readonly string $type,
        public readonly string $format,
        public readonly ?string $period,
        public readonly ?string $dateFrom,
        public readonly ?string $dateTo,
        public readonly string $userEmail,
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(ReportService $reportService): void
    {
        // Ensure the exports directory exists
        Storage::makeDirectory('exports');

        $filename = $this->buildFilename();
        $path     = 'exports/' . $filename;

        if ($this->type === 'sales') {
            $reportData = $reportService->getSalesReport(
                period:   $this->period ?? 'daily',
                dateFrom: $this->dateFrom,
                dateTo:   $this->dateTo,
            );
        } else {
            $reportData = $reportService->getStockReport();
        }

        if ($this->format === 'excel') {
            $this->generateExcel($reportData, $path);
        } else {
            $this->generatePdf($reportData, $path);
        }

        // Send email notification with the file attached
        Mail::to($this->userEmail)->send(
            new ReportExportReady(
                type:     $this->type,
                format:   $this->format,
                filename: $filename,
                filePath: $path,
            )
        );
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Build a descriptive filename for the exported file.
     */
    private function buildFilename(): string
    {
        $typePart   = $this->type === 'sales' ? 'penjualan' : 'stok';
        $datePart   = now()->format('Y-m-d_His');
        $extension  = $this->format === 'excel' ? 'xlsx' : 'pdf';

        return "laporan-{$typePart}-{$datePart}.{$extension}";
    }

    /**
     * Generate an Excel file and store it.
     */
    private function generateExcel(array $reportData, string $storagePath): void
    {
        $export = $this->type === 'sales'
            ? new SalesReportExport($reportData)
            : new StockReportExport($reportData);

        // Store to the local disk (storage/app/)
        Excel::store($export, $storagePath, 'local');
    }

    /**
     * Generate a PDF file and store it.
     */
    private function generatePdf(array $reportData, string $storagePath): void
    {
        $view = $this->type === 'sales' ? 'reports.sales-pdf' : 'reports.stock-pdf';

        $pdf = Pdf::loadView($view, ['report' => $reportData]);

        Storage::disk('local')->put($storagePath, $pdf->output());
    }
}
