<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Reservation;
use App\Models\Table;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

/**
 * TableController — Admin management of restaurant tables and QR codes.
 *
 * Implements QRScannerInterface responsibilities:
 *   - generateQRCode(tableId): creates a unique token stored in qr_code column
 *   - validateQRCode(qrCode): looks up table by token
 *   - regenerateQRCode(tableId): replaces old token with a new one
 *
 * Validates: Requirements 2.4, 2.5, 10.1, 10.2, 10.5
 */
class TableController extends Controller
{
    // -------------------------------------------------------------------------
    // CRUD — Admin
    // -------------------------------------------------------------------------

    /**
     * List all tables with their status and QR code URL.
     *
     * GET /api/admin/tables
     */
    public function index(Request $request): JsonResponse
    {
        $paginator = Table::orderBy('table_number')->paginate($request->integer('per_page', 10));
        $tables = $paginator->getCollection()->map(function (Table $table) {
            return $this->formatTable($table);
        });

        return response()->json([
            'data' => $tables,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
        ]);
    }

    /**
     * List tables for customer reservation forms.
     */
    public function customerIndex(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date' => ['nullable', 'date'],
            'time' => ['nullable', 'date_format:H:i'],
        ]);

        $date = $validated['date'] ?? null;
        $time = $validated['time'] ?? null;

        $bookedTableIds = [];
        if ($date && $time) {
            $bookedTableIds = Reservation::where('date', $date)
                ->where('time', $time)
                ->whereNotIn('status', ['cancelled'])
                ->pluck('table_id')
                ->all();
        }

        $tables = Table::orderBy('table_number')->get()->map(fn (Table $table) => [
            'id'           => $table->id,
            'table_number' => $table->table_number,
            'status'       => $table->status,
            'available'    => ! in_array($table->id, $bookedTableIds, true),
        ]);

        return response()->json(['data' => $tables]);
    }

    /**
     * Show a single table.
     *
     * GET /api/admin/tables/{table}
     */
    public function show(Table $table): JsonResponse
    {
        return response()->json(['data' => $this->formatTable($table)]);
    }

    /**
     * Create a new table and generate a unique QR code for it.
     *
     * POST /api/admin/tables
     *
     * Validates: Requirement 10.2 — "WHEN admin adds a new table, THE System SHALL
     * create a table record, generate a unique QR code, and display the new table."
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'table_number' => ['required', 'string', 'max:50', 'unique:table,table_number'],
        ]);

        // Generate a unique QR token (task 5.2 & 5.3)
        $token = $this->generateUniqueToken();

        $table = Table::create([
            'table_number' => $data['table_number'],
            'qr_code'      => $token,
            'status'       => 'available',
        ]);

        return response()->json([
            'message' => 'Meja berhasil dibuat.',
            'data'    => $this->formatTable($table),
        ], 201);
    }

    /**
     * Update table data and regenerate QR code.
     *
     * PUT/PATCH /api/admin/tables/{table}
     *
     * Validates: Requirement 2.5 — "WHEN admin updates table data, THE System SHALL
     * regenerate the QR code and deactivate the old one."
     */
    public function update(Request $request, Table $table): JsonResponse
    {
        $data = $request->validate([
            'table_number' => ['sometimes', 'string', 'max:50', 'unique:table,table_number,' . $table->id],
            'status'       => ['sometimes', 'in:available,occupied'],
        ]);

        // Regenerate QR code whenever table data is updated (task 5.4)
        $data['qr_code'] = $this->generateUniqueToken();

        $table->update($data);

        return response()->json([
            'message' => 'Meja berhasil diperbarui. QR code baru telah dibuat.',
            'data'    => $this->formatTable($table),
        ]);
    }

    /**
     * Delete a table.
     *
     * DELETE /api/admin/tables/{table}
     */
    public function destroy(Table $table): JsonResponse
    {
        $table->delete();

        return response()->json(['message' => 'Meja berhasil dihapus.']);
    }

    /**
     * Manually regenerate QR code for a table (task 5.4).
     *
     * POST /api/admin/tables/{table}/regenerate-qr
     *
     * Validates: Requirement 2.5 — old QR code is deactivated (replaced) and a new one is created.
     */
    public function regenerateQr(Table $table): JsonResponse
    {
        $oldToken = $table->qr_code;

        $table->update(['qr_code' => $this->generateUniqueToken()]);

        return response()->json([
            'message'   => 'QR code berhasil diregenerasi. QR code lama tidak lagi valid.',
            'old_token' => $oldToken,
            'data'      => $this->formatTable($table),
        ]);
    }

    /**
     * Manually update table status (task 5.7).
     *
     * PATCH /api/admin/tables/{table}/status
     *
     * Validates: Requirement 10.5 — "THE System SHALL allow admin to change table status manually."
     */
    public function updateStatus(Request $request, Table $table): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:available,occupied'],
        ]);

        $table->update(['status' => $data['status']]);

        return response()->json([
            'message' => 'Status meja berhasil diperbarui.',
            'data'    => $this->formatTable($table),
        ]);
    }

    // -------------------------------------------------------------------------
    // QR Code Scan Endpoint (task 5.5)
    // -------------------------------------------------------------------------

    /**
     * Validate a QR code token and redirect to Customer_App (task 5.5).
     *
     * GET /scan/{qrCode}
     *
     * Validates: Requirement 2.1 — "WHEN customer scans a valid QR code, THE QR_Scanner
     * SHALL identify the table and redirect to Customer_App in < 3 seconds."
     * Validates: Requirement 2.2 — "IF QR code is not registered, THEN show error message."
     * Validates: Requirement 10.4 — "WHEN customer scans QR code, THE System SHALL update
     * table status to occupied."
     */
    public function scan(string $qrCode): \Illuminate\Http\RedirectResponse|JsonResponse
    {
        $table = Table::where('qr_code', $qrCode)->first();

        if (! $table) {
            return response()->json([
                'message' => 'QR code tidak valid. Silakan pindai ulang atau hubungi pelayan.',
            ], 404);
        }

        // Update table status to occupied (task 5.6 — scan triggers occupied)
        $table->update(['status' => 'occupied']);

        return redirect()->route('customer.app', ['table_id' => $table->id]);
    }

    // -------------------------------------------------------------------------
    // Internal helpers
    // -------------------------------------------------------------------------

    /**
     * Generate a unique SHA-256 token for a QR code.
     *
     * The token is stored in the qr_code column and embedded in the scan URL:
     *   {APP_URL}/scan/{token}
     *
     * Uniqueness is guaranteed by checking the database before returning.
     */
    private function generateUniqueToken(): string
    {
        do {
            $token = hash('sha256', Str::uuid()->toString() . microtime(true) . random_bytes(16));
        } while (Table::where('qr_code', $token)->exists());

        return $token;
    }

    /**
     * Format a table record for API responses, including the full scan URL.
     */
    private function formatTable(Table $table): array
    {
        $scanUrl = url('/scan/' . $table->qr_code);
        $qrCodeSvg = QrCode::format('svg')
            ->size(240)
            ->margin(2)
            ->generate($scanUrl);

        return [
            'id'           => $table->id,
            'table_number' => $table->table_number,
            'status'       => $table->status,
            'qr_code'      => $table->qr_code,
            'qr_scan_url'  => $scanUrl,
            'qr_code_svg'  => (string) $qrCodeSvg,
            'created_at'   => $table->created_at,
            'updated_at'   => $table->updated_at,
        ];
    }
}
