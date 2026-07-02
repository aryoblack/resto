<?php

namespace App\Services;

use App\Models\Inventory;
use App\Models\Order;
use App\Models\OrderItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * ReportService — handles all report data aggregation for the Report_Engine.
 *
 * Validates: Requirements 15.1, 15.2, 15.3, 15.5, 15.6
 */
class ReportService
{
    /**
     * Get sales report for a given period with grouped breakdown.
     *
     * Returns per-period rows (period_label, total_revenue, order_count,
     * average_order_value) plus summary totals, top menus, and payment
     * method breakdown.
     *
     * @param  string       $period    'daily' | 'weekly' | 'monthly'
     * @param  string|null  $dateFrom  ISO date string Y-m-d (optional)
     * @param  string|null  $dateTo    ISO date string Y-m-d (optional)
     * @return array{
     *   period: string,
     *   date_from: string,
     *   date_to: string,
     *   data: array<int, array{period_label: string, total_revenue: float, order_count: int, average_order_value: float}>,
     *   summary: array{total_revenue: float, order_count: int, avg_order_value: float},
     *   top_menus: array,
     *   payment_method_breakdown: array
     * }
     */
    public function getSalesReport(string $period = 'daily', ?string $dateFrom = null, ?string $dateTo = null): array
    {
        [$from, $to] = $this->resolveDateRange($period, $dateFrom, $dateTo);

        $fromStart = $from->copy()->startOfDay();
        $toEnd     = $to->copy()->endOfDay();

        // ── 1. Grouped period data ────────────────────────────────────────────
        $groupedData = $this->buildGroupedData($period, $fromStart, $toEnd);

        // ── 2. Full order collection for top-menus & payment breakdown ────────
        $orders = Order::with('orderItems.menu')
            ->where('payment_status', 'paid')
            ->whereBetween('created_at', [$fromStart, $toEnd])
            ->get();

        // ── 3. Summary totals ─────────────────────────────────────────────────
        $totalRevenue  = (float) $orders->sum('total_price');
        $orderCount    = $orders->count();
        $avgOrderValue = $orderCount > 0 ? round($totalRevenue / $orderCount, 2) : 0.0;

        // ── 4. Top 5 menus by quantity sold ───────────────────────────────────
        $menuSales = [];
        foreach ($orders as $order) {
            foreach ($order->orderItems as $item) {
                $menuId = $item->menu_id;
                if (! isset($menuSales[$menuId])) {
                    $menuSales[$menuId] = [
                        'menu_id'        => $menuId,
                        'menu_name'      => $item->menu ? $item->menu->name : 'Unknown',
                        'total_quantity' => 0,
                        'total_revenue'  => 0.0,
                    ];
                }
                $menuSales[$menuId]['total_quantity'] += $item->quantity;
                $menuSales[$menuId]['total_revenue']  += (float) $item->price_at_time * $item->quantity;
            }
        }
        usort($menuSales, fn ($a, $b) => $b['total_quantity'] <=> $a['total_quantity']);
        $topMenus = array_slice(array_values($menuSales), 0, 5);

        // ── 5. Payment method breakdown ───────────────────────────────────────
        $paymentBreakdown = $orders->groupBy('payment_method')
            ->map(fn ($group) => [
                'count'   => $group->count(),
                'revenue' => (float) $group->sum('total_price'),
            ])
            ->toArray();

        return [
            'period'                   => $period,
            'date_from'                => $from->toDateString(),
            'date_to'                  => $to->toDateString(),
            'data'                     => $groupedData,
            'summary'                  => [
                'total_revenue'  => $totalRevenue,
                'order_count'    => $orderCount,
                'avg_order_value' => $avgOrderValue,
            ],
            'top_menus'                => $topMenus,
            'payment_method_breakdown' => $paymentBreakdown,
        ];
    }

    /**
     * Build the per-period grouped rows using a DB aggregate query.
     *
     * Each row contains:
     *   - period_label        : human-readable label for the bucket
     *   - total_revenue       : sum of total_price for paid orders in that bucket
     *   - order_count         : number of paid orders in that bucket
     *   - average_order_value : total_revenue / order_count (0 when no orders)
     *
     * @return array<int, array{period_label: string, total_revenue: float, order_count: int, average_order_value: float}>
     */
    private function buildGroupedData(string $period, Carbon $from, Carbon $to): array
    {
        switch ($period) {
            case 'monthly':
                $rows = DB::table('order')
                    ->where('payment_status', 'paid')
                    ->whereBetween('created_at', [$from, $to])
                    ->select(
                        DB::raw("DATE_FORMAT(created_at, '%Y-%m') AS bucket"),
                        DB::raw('SUM(total_price) AS total_revenue'),
                        DB::raw('COUNT(*) AS order_count'),
                    )
                    ->groupBy('bucket')
                    ->orderBy('bucket')
                    ->get();

                return $rows->map(function ($row) {
                    $avg = $row->order_count > 0
                        ? round((float) $row->total_revenue / $row->order_count, 2)
                        : 0.0;

                    // Format: "Jan 2025"
                    $label = Carbon::createFromFormat('Y-m', $row->bucket)->translatedFormat('M Y');

                    return [
                        'period_label'        => $label,
                        'total_revenue'       => (float) $row->total_revenue,
                        'order_count'         => (int) $row->order_count,
                        'average_order_value' => $avg,
                    ];
                })->values()->toArray();

            case 'weekly':
                $rows = DB::table('order')
                    ->where('payment_status', 'paid')
                    ->whereBetween('created_at', [$from, $to])
                    ->select(
                        DB::raw("DATE_FORMAT(created_at, '%x-W%v') AS bucket"),
                        DB::raw('SUM(total_price) AS total_revenue'),
                        DB::raw('COUNT(*) AS order_count'),
                    )
                    ->groupBy('bucket')
                    ->orderBy('bucket')
                    ->get();

                return $rows->map(function ($row) {
                    $avg = $row->order_count > 0
                        ? round((float) $row->total_revenue / $row->order_count, 2)
                        : 0.0;

                    // bucket format: "2025-W03" → label: "Week 3, 2025"
                    [$year, $weekPart] = explode('-W', $row->bucket);
                    $label = 'Week ' . ltrim($weekPart, '0') . ', ' . $year;

                    return [
                        'period_label'        => $label,
                        'total_revenue'       => (float) $row->total_revenue,
                        'order_count'         => (int) $row->order_count,
                        'average_order_value' => $avg,
                    ];
                })->values()->toArray();

            default: // 'daily'
                $rows = DB::table('order')
                    ->where('payment_status', 'paid')
                    ->whereBetween('created_at', [$from, $to])
                    ->select(
                        DB::raw("DATE(created_at) AS bucket"),
                        DB::raw('SUM(total_price) AS total_revenue'),
                        DB::raw('COUNT(*) AS order_count'),
                    )
                    ->groupBy('bucket')
                    ->orderBy('bucket')
                    ->get();

                return $rows->map(function ($row) {
                    $avg = $row->order_count > 0
                        ? round((float) $row->total_revenue / $row->order_count, 2)
                        : 0.0;

                    // Format: "2025-01-15" → "15 Jan 2025"
                    $label = Carbon::parse($row->bucket)->translatedFormat('d M Y');

                    return [
                        'period_label'        => $label,
                        'total_revenue'       => (float) $row->total_revenue,
                        'order_count'         => (int) $row->order_count,
                        'average_order_value' => $avg,
                    ];
                })->values()->toArray();
        }
    }

    /**
     * Get stock opname report — current stock vs min stock for all inventory items.
     *
     * @return array
     */
    public function getStockReport(): array
    {
        $items = Inventory::orderBy('ingredient_name')->get();

        $data = $items->map(fn ($item) => [
            'id'              => $item->id,
            'ingredient_name' => $item->ingredient_name,
            'unit'            => $item->unit,
            'current_stock'   => (float) $item->current_stock,
            'min_stock'       => (float) $item->min_stock,
            'supplier'        => $item->supplier,
            'is_critical'     => $item->isCriticalStock(),
        ])->values()->toArray();

        $criticalCount = collect($data)->where('is_critical', true)->count();

        return [
            'items'          => $data,
            'total_items'    => count($data),
            'critical_count' => $criticalCount,
        ];
    }

    /**
     * Get dashboard metrics for today.
     *
     * @return array
     */
    public function getDashboardMetrics(): array
    {
        $today = Carbon::today();

        $todayOrders = Order::where('payment_status', 'paid')
            ->whereDate('created_at', $today)
            ->get();

        $todayRevenue = (float) $todayOrders->sum('total_price');
        $todayOrderCount = $todayOrders->count();

        // Count unique customers who ordered today (non-null user_id)
        $todayCustomers = Order::whereDate('created_at', $today)
            ->whereNotNull('user_id')
            ->distinct('user_id')
            ->count('user_id');

        // Count inventory items where current_stock <= min_stock
        $criticalStockCount = Inventory::whereColumn('current_stock', '<=', 'min_stock')->count();

        return [
            'today_revenue'       => $todayRevenue,
            'today_orders'        => $todayOrderCount,
            'today_customers'     => $todayCustomers,
            'critical_stock_count' => $criticalStockCount,
        ];
    }

    /**
     * Get hourly revenue data for a given date (default: today).
     * Returns an array of 24 entries, one per hour (0–23).
     *
     * @param  string|null  $date  ISO date string (Y-m-d)
     * @return array
     */
    public function getHourlyRevenue(?string $date = null): array
    {
        $targetDate = $date ? Carbon::parse($date) : Carbon::today();

        $rows = Order::where('payment_status', 'paid')
            ->whereDate('created_at', $targetDate)
            ->select(
                DB::raw('HOUR(created_at) as hour'),
                DB::raw('SUM(total_price) as revenue')
            )
            ->groupBy(DB::raw('HOUR(created_at)'))
            ->pluck('revenue', 'hour')
            ->toArray();

        // Build a full 24-hour array, filling missing hours with 0
        $hourly = [];
        for ($h = 0; $h < 24; $h++) {
            $hourly[] = [
                'hour'    => $h,
                'revenue' => isset($rows[$h]) ? (float) $rows[$h] : 0.0,
            ];
        }

        return $hourly;
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Resolve the date range based on period or explicit from/to dates.
     *
     * Defaults when no explicit range is provided:
     *   daily   → last 30 days  (today − 29 days … today)
     *   weekly  → last 12 weeks (12 weeks ago Monday … this Sunday)
     *   monthly → last 12 months (12 months ago, 1st … end of current month)
     *
     * @return array{Carbon, Carbon}
     */
    private function resolveDateRange(string $period, ?string $dateFrom, ?string $dateTo): array
    {
        if ($dateFrom && $dateTo) {
            return [Carbon::parse($dateFrom), Carbon::parse($dateTo)];
        }

        $now = Carbon::now();

        return match ($period) {
            'weekly'  => [
                $now->copy()->subWeeks(11)->startOfWeek(),
                $now->copy()->endOfWeek(),
            ],
            'monthly' => [
                $now->copy()->subMonths(11)->startOfMonth(),
                $now->copy()->endOfMonth(),
            ],
            default   => [ // daily — last 30 days
                $now->copy()->subDays(29)->startOfDay(),
                $now->copy()->endOfDay(),
            ],
        };
    }
}
