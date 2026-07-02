<?php

namespace App\Http\Controllers;

use App\Exports\SalesReportExport;
use App\Exports\StockReportExport;
use App\Jobs\ExportReportJob;
use App\Services\ReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Maatwebsite\Excel\Facades\Excel;

/**
 * ReportController — admin-only endpoints for the Report_Engine.
 *
 * Routes:
 *   GET  /api/admin/reports/sales              → salesReport
 *   GET  /api/admin/reports/stock              → stockReport
 *   GET  /api/admin/reports/dashboard          → dashboardMetrics
 *   GET  /api/admin/reports/hourly-revenue     → hourlyRevenue
 *   POST /api/admin/reports/export/sales       → exportSales
 *   POST /api/admin/reports/export/stock       → exportStock
 *
 * Validates: Requirements 15.1, 15.2, 15.3, 15.4, 15.5, 15.6
 */
class ReportController extends Controller
{
    public function __construct(
        private readonly ReportService $reportService,
    ) {
    }

    // -------------------------------------------------------------------------
    // 13.2 / 13.3 — Sales report
    // -------------------------------------------------------------------------

    /**
     * GET /api/admin/reports/sales
     *
     * Query params:
     *   period    string  'daily' | 'weekly' | 'monthly'  (default: daily)
     *   date_from string  Y-m-d (optional)
     *   date_to   string  Y-m-d (optional)
     */
    public function salesReport(Request $request): JsonResponse
    {
        $request->validate([
            'period'    => 'sometimes|in:daily,weekly,monthly',
            'date_from' => 'sometimes|date',
            'date_to'   => 'sometimes|date|after_or_equal:date_from',
        ]);

        $data = $this->reportService->getSalesReport(
            period:   $request->input('period', 'daily'),
            dateFrom: $request->input('date_from'),
            dateTo:   $request->input('date_to'),
        );

        return response()->json([
            'message' => 'Laporan penjualan berhasil diambil.',
            'data'    => $data,
        ]);
    }

    // -------------------------------------------------------------------------
    // 13.4 — Stock report
    // -------------------------------------------------------------------------

    /**
     * GET /api/admin/reports/stock
     */
    public function stockReport(): JsonResponse
    {
        $data = $this->reportService->getStockReport();

        return response()->json([
            'message' => 'Laporan stok berhasil diambil.',
            'data'    => $data,
        ]);
    }

    // -------------------------------------------------------------------------
    // 13.9 — Dashboard metrics
    // -------------------------------------------------------------------------

    /**
     * GET /api/admin/reports/dashboard
     */
    public function dashboardMetrics(): JsonResponse
    {
        $data = $this->reportService->getDashboardMetrics();

        return response()->json([
            'message' => 'Metrik dashboard berhasil diambil.',
            'data'    => $data,
        ]);
    }

    // -------------------------------------------------------------------------
    // 13.8 — Hourly revenue chart data
    // -------------------------------------------------------------------------

    /**
     * GET /api/admin/reports/hourly-revenue
     *
     * Query params:
     *   date  string  Y-m-d (optional, default: today)
     */
    public function hourlyRevenue(Request $request): JsonResponse
    {
        $request->validate([
            'date' => 'sometimes|date',
        ]);

        $data = $this->reportService->getHourlyRevenue($request->input('date'));

        return response()->json([
            'message' => 'Data pendapatan per jam berhasil diambil.',
            'data'    => $data,
        ]);
    }

    // -------------------------------------------------------------------------
    // 13.5 / 13.6 / 13.7 — Export sales report
    // -------------------------------------------------------------------------

    /**
     * POST /api/admin/reports/export/sales
     *
     * Body params:
     *   format    string  'excel' | 'pdf'  (required)
     *   period    string  'daily' | 'weekly' | 'monthly'  (default: daily)
     *   date_from string  Y-m-d (optional)
     *   date_to   string  Y-m-d (optional)
     *   async     bool    true to queue the job (default: false)
     */
    public function exportSales(Request $request): mixed
    {
        $request->validate([
            'format'    => 'required|in:excel,pdf',
            'period'    => 'sometimes|in:daily,weekly,monthly',
            'date_from' => 'sometimes|date',
            'date_to'   => 'sometimes|date|after_or_equal:date_from',
            'async'     => 'sometimes|boolean',
        ]);

        $format   = $request->input('format');
        $period   = $request->input('period', 'daily');
        $dateFrom = $request->input('date_from');
        $dateTo   = $request->input('date_to');
        $async    = $request->boolean('async', false);

        // Queue the job for large/async exports
        if ($async) {
            ExportReportJob::dispatch(
                type: 'sales',
                format: $format,
                period: $period,
                dateFrom: $dateFrom,
                dateTo: $dateTo,
                userEmail: $request->user()->email,
            );

            return response()->json([
                'message' => 'Ekspor laporan sedang diproses. Anda akan menerima email saat selesai.',
            ], 202);
        }

        $reportData = $this->reportService->getSalesReport($period, $dateFrom, $dateTo);

        if ($format === 'excel') {
            return Excel::download(
                new SalesReportExport($reportData),
                'laporan-penjualan-' . now()->format('Y-m-d') . '.xlsx'
            );
        }

        // PDF
        $pdf = Pdf::loadView('reports.sales-pdf', ['report' => $reportData]);

        return $pdf->download('laporan-penjualan-' . now()->format('Y-m-d') . '.pdf');
    }

    // -------------------------------------------------------------------------
    // 13.5 / 13.6 / 13.7 — Export stock report
    // -------------------------------------------------------------------------

    /**
     * POST /api/admin/reports/export/stock
     *
     * Body params:
     *   format  string  'excel' | 'pdf'  (required)
     *   async   bool    true to queue the job (default: false)
     */
    public function exportStock(Request $request): mixed
    {
        $request->validate([
            'format' => 'required|in:excel,pdf',
            'async'  => 'sometimes|boolean',
        ]);

        $format = $request->input('format');
        $async  = $request->boolean('async', false);

        if ($async) {
            ExportReportJob::dispatch(
                type: 'stock',
                format: $format,
                period: null,
                dateFrom: null,
                dateTo: null,
                userEmail: $request->user()->email,
            );

            return response()->json([
                'message' => 'Ekspor laporan stok sedang diproses. Anda akan menerima email saat selesai.',
            ], 202);
        }

        $reportData = $this->reportService->getStockReport();

        if ($format === 'excel') {
            return Excel::download(
                new StockReportExport($reportData),
                'laporan-stok-' . now()->format('Y-m-d') . '.xlsx'
            );
        }

        // PDF
        $pdf = Pdf::loadView('reports.stock-pdf', ['report' => $reportData]);

        return $pdf->download('laporan-stok-' . now()->format('Y-m-d') . '.pdf');
    }
}
