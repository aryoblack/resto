<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Color;

/**
 * StockReportExport — exports the stock opname report to Excel (.xlsx).
 *
 * Produces a single sheet with:
 *   - A summary header section (total items, critical count, export date)
 *   - A data table with columns: Nama Bahan, Satuan, Stok Saat Ini, Stok Minimal, Supplier, Status
 *   - Critical rows (is_critical = true) are highlighted in light red
 *
 * Validates: Requirement 15.4
 */
class StockReportExport implements FromArray, WithTitle, WithStyles, WithColumnWidths
{
    /** Row index (1-based) where the data table header starts. */
    private int $tableHeaderRow = 7;

    public function __construct(
        private readonly array $reportData,
    ) {
    }

    public function title(): string
    {
        return 'Laporan Stok';
    }

    /**
     * Build the full sheet content as a 2-D array.
     *
     * Layout:
     *   Row 1 : "LAPORAN STOK OPNAME"
     *   Row 2 : Tanggal Ekspor | <date>
     *   Row 3 : Total Bahan    | <count>
     *   Row 4 : Stok Kritis    | <count>
     *   Row 5 : (blank)
     *   Row 6 : Keterangan warna
     *   Row 7 : Table heading
     *   Row 8+ : Data rows
     */
    public function array(): array
    {
        $items        = $this->reportData['items']          ?? [];
        $totalItems   = $this->reportData['total_items']    ?? count($items);
        $criticalCount = $this->reportData['critical_count'] ?? 0;

        $rows = [];

        // ── Header section ────────────────────────────────────────────────────
        $rows[] = ['LAPORAN STOK OPNAME', '', '', '', '', ''];
        $rows[] = ['Tanggal Ekspor', now()->format('d/m/Y H:i'), '', '', '', ''];
        $rows[] = ['Total Bahan Baku', $totalItems, '', '', '', ''];
        $rows[] = ['Jumlah Stok Kritis', $criticalCount, '', '', '', ''];
        $rows[] = ['', '', '', '', '', ''];
        $rows[] = ['* Baris berwarna merah = stok kritis (stok saat ini ≤ stok minimal)', '', '', '', '', ''];

        // ── Table heading (row 7) ─────────────────────────────────────────────
        $rows[] = [
            'Nama Bahan',
            'Satuan',
            'Stok Saat Ini',
            'Stok Minimal',
            'Supplier',
            'Status',
        ];

        // ── Data rows ─────────────────────────────────────────────────────────
        foreach ($items as $item) {
            $rows[] = [
                $item['ingredient_name'] ?? '-',
                $item['unit']            ?? '-',
                (float) ($item['current_stock'] ?? 0),
                (float) ($item['min_stock']     ?? 0),
                $item['supplier']        ?? '-',
                ($item['is_critical'] ?? false) ? '⚠ KRITIS' : 'Normal',
            ];
        }

        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        $items = $this->reportData['items'] ?? [];

        $styles = [
            // Title row
            1 => ['font' => ['bold' => true, 'size' => 14]],
            // Table heading row
            $this->tableHeaderRow => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType'   => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FFD9D9D9'],
                ],
            ],
        ];

        // Highlight critical rows in light red
        foreach ($items as $index => $item) {
            if (! empty($item['is_critical'])) {
                // Data rows start at tableHeaderRow + 1
                $rowNumber = $this->tableHeaderRow + 1 + $index;
                $styles[$rowNumber] = [
                    'fill' => [
                        'fillType'   => Fill::FILL_SOLID,
                        'startColor' => ['argb' => 'FFFFC7CE'],
                    ],
                    'font' => ['color' => ['argb' => 'FF9C0006']],
                ];
            }
        }

        return $styles;
    }

    public function columnWidths(): array
    {
        return [
            'A' => 30,
            'B' => 12,
            'C' => 18,
            'D' => 16,
            'E' => 30,
            'F' => 14,
        ];
    }
}
