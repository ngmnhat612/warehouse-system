<?php

namespace App\Http\Controllers;

use App\Exports\ReportExport;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportController extends Controller
{
    // ── Dùng lại ở cả index, exportExcel, exportPdf ───────────────────────────
    private function buildReportData(Request $request): array
    {
        $dateFrom = $request->date_from ?? now()->startOfMonth()->toDateString();
        $dateTo   = $request->date_to   ?? now()->toDateString();

        $totalReceiptQty = DB::table('stock_ledger')
            ->whereBetween('transaction_date', [$dateFrom, $dateTo . ' 23:59:59'])
            ->where('direction', 1)
            ->when($request->product_id, fn($q) => $q->where('product_id', $request->product_id))
            ->sum('quantity');

        $totalIssueQty = DB::table('stock_ledger')
            ->whereBetween('transaction_date', [$dateFrom, $dateTo . ' 23:59:59'])
            ->where('direction', 2)
            ->when($request->product_id, fn($q) => $q->where('product_id', $request->product_id))
            ->sum('quantity');

        $totalReceiptVouchers = DB::table('stock_receipts')
            ->whereBetween('created_at', [$dateFrom, $dateTo . ' 23:59:59'])
            ->where('status', 4)
            ->count();

        $totalIssueVouchers = DB::table('stock_issues')
            ->whereBetween('issue_date', [$dateFrom, $dateTo])
            ->where('status', 4)
            ->count();

        $stockQuery   = DB::table('stock');
        if ($request->product_id) {
            $stockQuery->where('product_id', $request->product_id);
        }
        $closingStock = $stockQuery->sum('quantity');
        $openingStock = $closingStock - $totalReceiptQty + $totalIssueQty;

        $lowStockCount = DB::table('reorder_rules as rr')
            ->leftJoinSub(
                DB::table('stock')
                    ->selectRaw('product_id, location_id, SUM(quantity - reserved_qty) AS avail_qty')
                    ->groupBy('product_id', 'location_id'),
                'st',
                fn($j) => $j->on('st.product_id',  '=', 'rr.product_id')
                            ->on('st.location_id', '=', 'rr.location_id')
            )
            ->where('rr.status', 1)
            ->whereRaw('COALESCE(st.avail_qty, 0) < rr.min_qty')
            ->count();

        $expiringSoonCount = DB::table('lots')
            ->whereNotNull('expiry_date')
            ->where('expiry_date', '<=', now()->addDays(30)->toDateString())
            ->where('status', 1)
            ->count();

        // Chart
        $chartData = DB::table('stock_ledger')
            ->selectRaw("CAST(transaction_date AS DATE) as day,
                SUM(CASE WHEN direction = 1 THEN quantity ELSE 0 END) as receipt_qty,
                SUM(CASE WHEN direction = 2 THEN quantity ELSE 0 END) as issue_qty")
            ->whereBetween('transaction_date', [$dateFrom, $dateTo . ' 23:59:59'])
            ->when($request->product_id, fn($q) => $q->where('product_id', $request->product_id))
            ->groupByRaw('CAST(transaction_date AS DATE)')
            ->orderBy('day')
            ->get();

        $chartLabels   = $chartData->pluck('day')->map(fn($d) => \Carbon\Carbon::parse($d)->format('d/m'))->toArray();
        $chartReceipts = $chartData->pluck('receipt_qty')->map(fn($v) => (float) $v)->toArray();
        $chartIssues   = $chartData->pluck('issue_qty')->map(fn($v) => (float) $v)->toArray();

        // Mục đích xuất (doughnut)
        $purposeRows = DB::table('stock_issues')
            ->selectRaw('issue_type, COUNT(*) as cnt')
            ->whereBetween('issue_date', [$dateFrom, $dateTo])
            ->where('status', 4)
            ->groupBy('issue_type')
            ->get()->keyBy('issue_type');

        $purposeLabels = ['Sản xuất', 'Bảo trì', 'Mượn', 'Khác'];
        $purposeData   = [
            (int) ($purposeRows->get(1)?->cnt ?? 0),
            (int) ($purposeRows->get(2)?->cnt ?? 0),
            (int) ($purposeRows->get(3)?->cnt ?? 0),
            (int) ($purposeRows->get(4)?->cnt ?? 0) + (int) ($purposeRows->get(5)?->cnt ?? 0),
        ];

        // Bảng NXT
        $allRows = DB::table('products as p')
            ->leftJoin('categories as c', 'p.category_id', '=', 'c.id')
            ->leftJoin('uoms as u', 'p.uom_id', '=', 'u.id')
            ->leftJoinSub(
                DB::table('stock')->selectRaw('product_id, SUM(quantity) as current_qty')
                    ->groupBy('product_id'),
                'st', 'st.product_id', '=', 'p.id'
            )
            ->leftJoinSub(
                DB::table('stock_ledger')
                    ->selectRaw("product_id,
                        SUM(CASE WHEN direction = 1 THEN quantity ELSE 0 END) as receipt_qty,
                        SUM(CASE WHEN direction = 2 THEN quantity ELSE 0 END) as issue_qty")
                    ->whereBetween('transaction_date', [$dateFrom, $dateTo . ' 23:59:59'])
                    ->groupBy('product_id'),
                'sl', 'sl.product_id', '=', 'p.id'
            )
            ->select(
                'p.id', 'p.code as product_code', 'p.name as product_name',
                'c.name as category_name', 'u.name as uom_name',
                DB::raw('COALESCE(sl.receipt_qty, 0) as receipt_qty'),
                DB::raw('COALESCE(sl.issue_qty,   0) as issue_qty'),
                DB::raw('COALESCE(st.current_qty, 0) as closing_qty'),
                DB::raw('COALESCE(st.current_qty, 0) - COALESCE(sl.receipt_qty, 0) + COALESCE(sl.issue_qty, 0) as opening_qty')
            )
            ->where('p.status', 1)
            ->when($request->category_id, fn($q) => $q->where('p.category_id', $request->category_id))
            ->when($request->product_id,  fn($q) => $q->where('p.id',           $request->product_id))
            ->orderBy('p.code')
            ->get();

        // Đồng bộ với ReportAlertController::fetchBelowMin:
        $lowStockItems = DB::table('reorder_rules as rr')
            ->join('products as p',  'p.id', '=', 'rr.product_id')
            ->join('locations as l', 'l.id', '=', 'rr.location_id')
            ->join('uoms as u',      'u.id', '=', 'p.uom_id')
            ->leftJoin('categories as c', 'c.id', '=', 'p.category_id')
            ->leftJoinSub(
                DB::table('stock')
                    ->selectRaw('product_id, location_id, SUM(quantity - reserved_qty) AS avail_qty')
                    ->groupBy('product_id', 'location_id'),
                'st',
                fn($j) => $j->on('st.product_id',  '=', 'rr.product_id')
                            ->on('st.location_id', '=', 'rr.location_id')
            )
            ->select(
                'p.code',
                'p.name',
                'c.name as category_name',
                'u.name as uom_name',
                'l.code as location_code',
                'rr.min_qty as min_stock',
                'rr.max_qty',
                DB::raw('COALESCE(st.avail_qty, 0) AS current_qty'),
                DB::raw('rr.min_qty - COALESCE(st.avail_qty, 0) AS shortage_qty'),
                DB::raw('CASE WHEN rr.max_qty > 0 THEN rr.max_qty - COALESCE(st.avail_qty, 0) ELSE NULL END AS order_qty')
            )
            ->where('rr.status', 1)
            ->where('p.status',  1)
            ->whereRaw('COALESCE(st.avail_qty, 0) < rr.min_qty')
            ->orderByRaw('(rr.min_qty - COALESCE(st.avail_qty, 0)) DESC')
            ->get();

        // Cảnh báo hàng sắp hết hạn — đồng bộ với ReportAlertController::fetchNearExpiry
        $_today   = now()->toDateString();
        $_horizon = now()->addDays(30)->toDateString();

        $_fromLots = DB::table('lots as lt')
            ->join('products as p',   'p.id', '=', 'lt.product_id')
            ->join('uoms as u',       'u.id', '=', 'p.uom_id')
            ->leftJoin('categories as c', 'c.id', '=', 'p.category_id')
            ->leftJoinSub(
                DB::table('stock')
                    ->selectRaw('lot_id, location_id, SUM(quantity) AS qty')
                    ->whereNotNull('lot_id')
                    ->where('quantity', '>', 0)
                    ->groupBy('lot_id', 'location_id'),
                'st', 'st.lot_id', '=', 'lt.id'
            )
            ->join('locations as lc', 'lc.id', '=', 'st.location_id')
            ->selectRaw("
                p.name AS product_name,
                lt.lot_number,
                lt.expiry_date,
                lc.code AS location_code,
                st.qty AS quantity,
                DATEDIFF(day, GETDATE(), lt.expiry_date) AS days_left
            ")
            ->where('lt.status', 1)
            ->whereNotNull('lt.expiry_date')
            ->whereBetween('lt.expiry_date', [$_today, $_horizon])
            ->where('st.qty', '>', 0);

        $_fromStock = DB::table('stock as s')
            ->join('products as p',   'p.id', '=', 's.product_id')
            ->join('locations as lc', 'lc.id', '=', 's.location_id')
            ->join('uoms as u',       'u.id', '=', 'p.uom_id')
            ->leftJoin('categories as c', 'c.id', '=', 'p.category_id')
            ->selectRaw("
                p.name AS product_name,
                NULL AS lot_number,
                s.expiry_date,
                lc.code AS location_code,
                SUM(s.quantity) AS quantity,
                DATEDIFF(day, GETDATE(), s.expiry_date) AS days_left
            ")
            ->whereNull('s.lot_id')
            ->whereNotNull('s.expiry_date')
            ->whereBetween('s.expiry_date', [$_today, $_horizon])
            ->where('s.quantity', '>', 0)
            ->groupBy('p.name', 's.expiry_date', 'lc.code');

        $expiringSoonItems = $_fromLots
            ->union($_fromStock)
            ->orderBy('days_left')
            ->get();

        return compact(
            'dateFrom', 'dateTo',
            'totalReceiptQty', 'totalIssueQty',
            'totalReceiptVouchers', 'totalIssueVouchers',
            'openingStock', 'closingStock',
            'lowStockCount', 'expiringSoonCount',
            'chartLabels', 'chartReceipts', 'chartIssues',
            'purposeLabels', 'purposeData',
            'allRows',
            'lowStockItems', 'expiringSoonItems',
        );
    }

    // ── index ─────────────────────────────────────────────────────────────────
    public function index(Request $request)
    {
        $categories = Category::where('status', 1)->orderBy('name')->get();
        $products   = Product::where('status', 1)->orderBy('name')->get();

        $data = $this->buildReportData($request);

        // Paginate allRows cho view
        $allRows = $data['allRows'];
        $reportRows = new \Illuminate\Pagination\LengthAwarePaginator(
            $allRows->forPage(\Illuminate\Pagination\Paginator::resolveCurrentPage(), 20),
            $allRows->count(),
            20,
            null,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('reports.index', array_merge($data, compact(
            'categories', 'products', 'reportRows'
        )));
    }

    // ── exportExcel ───────────────────────────────────────────────────────────
    public function exportExcel(Request $request)
    {
        $filters  = $request->only(['date_from', 'date_to', 'category_id', 'product_id']);
        $dateFrom = $filters['date_from'] ?? now()->startOfMonth()->toDateString();
        $dateTo   = $filters['date_to']   ?? now()->toDateString();
        $filename = "bao-cao-NXT_{$dateFrom}_{$dateTo}.xlsx";

        return Excel::download(new ReportExport($filters), $filename);
    }

    // ── exportPdf ─────────────────────────────────────────────────────────────
    public function exportPdf(Request $request)
    {
        $data = $this->buildReportData($request);

        // Truyền toàn bộ allRows vào PDF (không paginate)
        $reportRows = $data['allRows'];

        $pdf = Pdf::loadView('reports.pdf', array_merge($data, compact('reportRows')))
            ->setPaper('a4', 'landscape')
            ->setOptions([
                'defaultFont'  => 'DejaVu Sans',
                'isRemoteEnabled' => false,
                'isHtml5ParserEnabled' => true,
            ]);

        $dateFrom = $data['dateFrom'];
        $dateTo   = $data['dateTo'];

        return $pdf->download("bao-cao-NXT_{$dateFrom}_{$dateTo}.pdf");
    }
}
