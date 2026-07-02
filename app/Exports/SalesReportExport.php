<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;

/**
 * SalesReportExport — exports the sales report data to Excel (.xlsx).
 *
 * Implements WithMultipleSheets to produce:
 *   Sheet 1 — "Ringkasan"  : summary metrics + payment method breakdown + top menus
 *   Sheet 2 — "Detail Periode" : per-period rows (period_label, total_revenue, order_count, avg)
 *
 * Validates: Requirement 15.4
 */
class SalesReportExport implements WithMultipleSheets
{
    public function __construct(
        private readonly array $reportData,
    ) {
    }

    /**
     * Return the two sheets that make up the workbook.
     */
    public function sheets(): array
    {
        return [
            new SalesReportSummarySheet($this->reportData),
            new SalesReportDetailSheet($this->reportData),
        ];
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// Sheet 1 — Ringkasan (Summary)
// ─────────────────────────────────────────────────────────────────────────────

/**
 * @internal
 */
class SalesReportSummarySheet implements FromArray, WithTitle, WithStyles, WithColumnWidths
{
    public function __construct(private readonly array $reportData)
    {
    }

    public function title(): string
    {
        return 'Ringkasan';
    }

    public function array(): array
    {
        $summary  = $this->reportData['summary']  ?? [];
        $topMenus = $this->reportData['top_menus'] ?? [];
        $breakdown = $this->reportData['payment_method_breakdown'] ?? [];
        $period   = $this->reportData['period']    ?? '-';
        $dateFrom = $this->reportData['date_from'] ?? '-';
        $dateTo   = $this->reportData['date_to']   ?? '-';

        $rows = [];

        // ── Header info ───────────────────────────────────────────────────────
        $rows[] = ['LAPORAN PENJUALAN', ''];
        $rows[] = ['Periode', ucfirst($period)];
        $rows[] = ['Dari Tanggal', $dateFrom];
        $rows[] = ['Sampai Tanggal', $dateTo];
        $rows[] = ['', ''];

        // ── Summary metrics ───────────────────────────────────────────────────
        $rows[] = ['RINGKASAN METRIK', ''];
        $rows[] = ['Total Pendapatan', $this->formatCurrency($summary['total_revenue'] ?? 0)];
        $rows[] = ['Jumlah Pesanan', $summary['order_count'] ?? 0];
        $rows[] = ['Rata-rata Nilai Pesanan', $this->formatCurrency($summary['avg_order_value'] ?? 0)];
        $rows[] = ['', ''];

        // ── Payment method breakdown ──────────────────────────────────────────
        $rows[] = ['RINCIAN METODE PEMBAYARAN', ''];
        $rows[] = ['Metode Pembayaran', 'Jumlah Transaksi', 'Total Pendapatan'];
        foreach ($breakdown as $method => $info) {
            $rows[] = [
                strtoupper($method),
                $info['count'] ?? 0,
                $this->formatCurrency($info['revenue'] ?? 0),
            ];
        }
        $rows[] = ['', ''];

        // ── Top 5 menus ───────────────────────────────────────────────────────
        $rows[] = ['TOP 5 MENU TERLARIS', ''];
        $rows[] = ['Nama Menu', 'Total Terjual (Qty)', 'Total Pendapatan'];
        foreach ($topMenus as $menu) {
            $rows[] = [
                $menu['menu_name']      ?? '-',
                $menu['total_quantity'] ?? 0,
                $this->formatCurrency($menu['total_revenue'] ?? 0),
            ];
        }

        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            // Bold the title row
            1 => ['font' => ['bold' => true, 'size' => 14]],
            // Bold section headers
            6  => ['font' => ['bold' => true]],
            11 => ['font' => ['bold' => true]],
            12 => ['font' => ['bold' => true, 'italic' => true]],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 35,
            'B' => 25,
            'C' => 25,
        ];
    }

    private function formatCurrency(float $amount): string
    {
        return 'Rp ' . number_format($amount, 0, ',', '.');
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// Sheet 2 — Detail Periode
// ─────────────────────────────────────────────────────────────────────────────

/**
 * @internal
 */
class SalesReportDetailSheet implements FromArray, WithTitle, WithHeadings, WithStyles, WithColumnWidths, WithColumnFormatting
{
    public function __construct(private readonly array $reportData)
    {
    }

    public function title(): string
    {
        return 'Detail Periode';
    }

    public function headings(): array
    {
        return [
            'Periode',
            'Total Pendapatan',
            'Jumlah Pesanan',
            'Rata-rata Nilai Pesanan',
        ];
    }

    public function array(): array
    {
        $rows = [];

        foreach ($this->reportData['data'] ?? [] as $row) {
            $rows[] = [
                $row['period_label']        ?? '-',
                (float) ($row['total_revenue']       ?? 0),
                (int)   ($row['order_count']         ?? 0),
                (float) ($row['average_order_value'] ?? 0),
            ];
        }

        return $rows;
    }

    public function columnFormats(): array
    {
        return [
            'B' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'D' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        // Bold the heading row (row 1 because WithHeadings prepends it)
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 25,
            'B' => 22,
            'C' => 18,
            'D' => 28,
        ];
    }
}
