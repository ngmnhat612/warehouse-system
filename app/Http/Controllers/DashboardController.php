<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        // ── 1. CARDS KPI ──────────────────────────────────────────────────────

        $totalSkus       = DB::table('products')->where('status', 1)->count();
        $totalStock      = DB::table('stock')->sum('quantity');
        $pendingReceipts = DB::table('stock_receipts')->where('status', 2)->count();
        $pendingIssues   = DB::table('stock_issues')->where('status', 2)->count();

        // ── 2. HÀNG DƯỚI MỨC TỒN TỐI THIỂU ─────────────────────────────────
        // SQL Server không cho alias của subquery trong HAVING.
        // Dùng subquery trong SELECT, lọc bằng WHERE ở query ngoài (CTE pattern).
        $lowStockItems = DB::table(DB::raw('(
            SELECT
                rr.id,
                rr.min_qty,
                p.code  AS product_code,
                p.name  AS product_name,
                u.name  AS uom_name,
                l.code  AS location_code,
                COALESCE(s.total_qty, 0) AS current_stock
            FROM reorder_rules rr
            INNER JOIN products  p ON p.id = rr.product_id
            INNER JOIN uoms      u ON u.id = p.uom_id
            INNER JOIN locations l ON l.id = rr.location_id
            LEFT JOIN (
                SELECT product_id, location_id, SUM(available_qty) AS total_qty
                FROM stock
                GROUP BY product_id, location_id
            ) s ON s.product_id = rr.product_id AND s.location_id = rr.location_id
            WHERE rr.status = 1
        ) AS sub'))
        ->whereRaw('current_stock < min_qty')
        ->orderByRaw('(min_qty - current_stock) DESC')
        ->limit(10)
        ->get()
        ->map(fn($r) => (object) [
            'product_code'  => $r->product_code,
            'product_name'  => $r->product_name,
            'uom_name'      => $r->uom_name,
            'location_code' => $r->location_code,
            'current_stock' => (float) $r->current_stock,
            'min_qty'       => (float) $r->min_qty,
            'shortage'      => (float) $r->min_qty - (float) $r->current_stock,
        ]);

        // ── 3. LÔ HÀNG SẮP HẾT HẠN ──────────────────────────────────────────
        // Cảnh báo lot active, còn hàng, expiry_date trong 30 ngày tới
        $today     = Carbon::today()->toDateString();
        $alertDate = Carbon::today()->addDays(30)->toDateString();

        $expiringLots = DB::table('lots as lt')
            ->join('products as p', 'lt.product_id', '=', 'p.id')
            ->join('uoms as u', 'p.uom_id', '=', 'u.id')
            ->leftJoin(
                DB::raw('(SELECT lot_id, SUM(quantity) as lot_qty FROM stock WHERE lot_id IS NOT NULL GROUP BY lot_id) as s'),
                's.lot_id', '=', 'lt.id'
            )
            ->select(
                'lt.id',
                'lt.lot_number',
                'lt.expiry_date',
                'p.code as product_code',
                'p.name as product_name',
                'u.name as uom_name',
                DB::raw('COALESCE(s.lot_qty, 0) as current_qty'),
                // SQL Server: DATEDIFF(day, start, end)
                DB::raw('DATEDIFF(day, CAST(GETDATE() AS DATE), lt.expiry_date) as days_remaining')
            )
            ->where('lt.status', 1)
            ->whereBetween('lt.expiry_date', [$today, $alertDate])
            ->whereRaw('COALESCE(s.lot_qty, 0) > 0')
            ->orderBy('lt.expiry_date')
            ->limit(10)
            ->get();

        // ── 4. CHART — NHẬP/XUẤT 30 NGÀY ─────────────────────────────────────
        $start = Carbon::today()->subDays(29)->startOfDay();
        $end   = Carbon::today()->endOfDay();

        $inData = DB::table('stock_ledger')
            ->selectRaw('CAST(transaction_date AS DATE) as txn_day, SUM(quantity) as total')
            ->where('direction', 1)
            ->whereBetween('transaction_date', [$start, $end])
            ->groupByRaw('CAST(transaction_date AS DATE)')
            ->pluck('total', 'txn_day');

        $outData = DB::table('stock_ledger')
            ->selectRaw('CAST(transaction_date AS DATE) as txn_day, SUM(quantity) as total')
            ->where('direction', 2)
            ->whereBetween('transaction_date', [$start, $end])
            ->groupByRaw('CAST(transaction_date AS DATE)')
            ->pluck('total', 'txn_day');

        $chartLabels = $chartReceipts = $chartDeliveries = [];
        for ($i = 29; $i >= 0; $i--) {
            $day               = Carbon::today()->subDays($i)->toDateString();
            $chartLabels[]     = Carbon::today()->subDays($i)->format('d/m');
            $chartReceipts[]   = (float) ($inData[$day]  ?? 0);
            $chartDeliveries[] = (float) ($outData[$day] ?? 0);
        }

        // ── 5. 10 GIAO DỊCH MỚI NHẤT ─────────────────────────────────────────
        $recentTransactions = DB::table('stock_ledger as sl')
            ->join('products as p',    'sl.product_id',  '=', 'p.id')
            ->join('locations as l',   'sl.location_id', '=', 'l.id')
            ->join('uoms as u',        'p.uom_id',       '=', 'u.id')
            ->leftJoin('users as usr', 'sl.created_by',  '=', 'usr.id')
            ->select(
                'sl.transaction_type',
                'sl.reference_code',
                'sl.direction',
                'sl.quantity',
                'sl.balance_after',
                'sl.transaction_date',
                'p.code as product_code',
                'p.name as product_name',
                'l.code as location_code',
                'u.name as uom_name',
                'usr.name as created_by_name',
            )
            ->orderByDesc('sl.transaction_date')
            ->orderByDesc('sl.id')
            ->limit(10)
            ->get();

        return view('dashboard.index', compact(
            'totalSkus', 'totalStock', 'pendingReceipts', 'pendingIssues',
            'lowStockItems', 'expiringLots',
            'chartLabels', 'chartReceipts', 'chartDeliveries',
            'recentTransactions',
        ));
    }
}