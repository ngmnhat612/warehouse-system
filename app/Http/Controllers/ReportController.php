<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $dateFrom = $request->date_from ?? now()->startOfMonth()->toDateString();
        $dateTo   = $request->date_to   ?? now()->toDateString();

        // ── Danh sách filter ───────────────────────────────────────────
        $categories = Category::where('status', 1)->orderBy('name')->get();
        $products   = Product::where('status', 1)->orderBy('name')->get();

        // ── KPI cards ──────────────────────────────────────────────────
        $totalReceiptQty     = DB::table('stock_ledger')
            ->whereBetween('transaction_date', [$dateFrom, $dateTo])
            ->where('direction', 1)
            ->when($request->product_id, fn($q) => $q->where('product_id', $request->product_id))
            ->sum('quantity');

        $totalIssueQty       = DB::table('stock_ledger')
            ->whereBetween('transaction_date', [$dateFrom, $dateTo])
            ->where('direction', 2)
            ->when($request->product_id, fn($q) => $q->where('product_id', $request->product_id))
            ->sum('quantity');

        $totalReceiptVouchers = DB::table('stock_receipts')
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->where('status', 4) // COMPLETED
            ->count();

        $totalIssueVouchers   = DB::table('stock_issues')
            ->whereBetween('issue_date', [$dateFrom, $dateTo])
            ->where('status', 4) // COMPLETED
            ->count();

        // Tồn đầu kỳ / cuối kỳ (tổng toàn bộ hoặc theo product filter)
        $stockQuery = DB::table('stock');
        if ($request->product_id) {
            $stockQuery->where('product_id', $request->product_id);
        }
        $closingStock = $stockQuery->sum('quantity');
        $openingStock = $closingStock - $totalReceiptQty + $totalIssueQty;

        $lowStockCount      = DB::table('stock')
            ->join('products', 'stock.product_id', '=', 'products.id')
            ->whereRaw('stock.quantity <= products.min_stock AND products.min_stock > 0')
            ->count();

        $expiringSoonCount  = DB::table('lots')
            ->whereNotNull('expiry_date')
            ->where('expiry_date', '<=', now()->addDays(30)->toDateString())
            ->where('status', 1)
            ->count();

        // ── Biểu đồ nhập / xuất theo ngày ────────────────────────────
        $chartData = DB::table('stock_ledger')
            ->selectRaw("CAST(transaction_date AS DATE) as day,
                        SUM(CASE WHEN direction = 1 THEN quantity ELSE 0 END) as receipt_qty,
                        SUM(CASE WHEN direction = 2 THEN quantity ELSE 0 END) as issue_qty")
            ->whereBetween('transaction_date', [$dateFrom, $dateTo])
            ->groupByRaw('CAST(transaction_date AS DATE)')
            ->orderBy('day')
            ->get();

        $chartLabels   = $chartData->pluck('day')->map(fn($d) => \Carbon\Carbon::parse($d)->format('d/m'))->toArray();
        $chartReceipts = $chartData->pluck('receipt_qty')->toArray();
        $chartIssues   = $chartData->pluck('issue_qty')->toArray();

        // ── Biểu đồ mục đích xuất ─────────────────────────────────────
        $purposeRows  = DB::table('stock_issues')
            ->selectRaw('issue_type, COUNT(*) as cnt')
            ->whereBetween('issue_date', [$dateFrom, $dateTo])
            ->where('status', 4)
            ->groupBy('issue_type')
            ->get()
            ->keyBy('issue_type');

        $purposeLabels = ['Sản xuất', 'Bảo trì', 'Mượn', 'Trả NCC', 'Khác'];
        $purposeData   = [
            $purposeRows->get(1)?->cnt ?? 0,
            $purposeRows->get(2)?->cnt ?? 0,
            $purposeRows->get(3)?->cnt ?? 0,
            $purposeRows->get(4)?->cnt ?? 0,
            $purposeRows->get(5)?->cnt ?? 0,
        ];

        // ── Bảng tổng hợp Nhập / Xuất / Tồn theo mặt hàng ───────────
        $reportQuery = DB::table('products as p')
            ->leftJoin('categories as c', 'p.category_id', '=', 'c.id')
            ->leftJoin('uoms as u', 'p.uom_id', '=', 'u.id')
            ->leftJoinSub(
                DB::table('stock')->selectRaw('product_id, SUM(quantity) as current_qty')
                    ->groupBy('product_id'),  // ← thêm dòng này
                'st', 'st.product_id', '=', 'p.id'
            )
            ->leftJoinSub(
                DB::table('stock_ledger')
                    ->selectRaw("product_id,
                        SUM(CASE WHEN direction = 1 THEN quantity ELSE 0 END) as receipt_qty,
                        SUM(CASE WHEN direction = 2 THEN quantity ELSE 0 END) as issue_qty")
                    ->whereBetween('transaction_date', [$dateFrom, $dateTo])
                    ->groupBy('product_id'),
                'sl', 'sl.product_id', '=', 'p.id'
            )
            ->select(
                'p.id',
                'p.code as product_code',
                'p.name as product_name',
                'p.min_stock',
                'c.name as category_name',
                'u.name as uom_name',
                DB::raw('COALESCE(sl.receipt_qty, 0) as receipt_qty'),
                DB::raw('COALESCE(sl.issue_qty, 0) as issue_qty'),
                DB::raw('COALESCE(st.current_qty, 0) as closing_qty'),
                DB::raw('COALESCE(st.current_qty, 0) - COALESCE(sl.receipt_qty, 0) + COALESCE(sl.issue_qty, 0) as opening_qty')
            )
            ->where('p.status', 1)
            ->when($request->category_id, fn($q) => $q->where('p.category_id', $request->category_id))
            ->when($request->product_id,  fn($q) => $q->where('p.id', $request->product_id));

// \DB::enableQueryLog();
        $allRows = $reportQuery->orderBy('p.code')->get();
// dd(\DB::getQueryLog());
        $reportRows = new \Illuminate\Pagination\LengthAwarePaginator(
            $allRows->forPage(\Illuminate\Pagination\Paginator::resolveCurrentPage(), 20),
            $allRows->count(),
            20,
            null,
            ['path' => request()->url()]
        );

        // ── Cảnh báo: hàng dưới ngưỡng ───────────────────────────────
        $lowStockItems = DB::table('products as p')
            ->join('stock as s', 's.product_id', '=', 'p.id')
            ->selectRaw('p.code, p.name, p.min_stock, SUM(s.quantity) as current_qty')
            ->where('p.status', 1)
            ->whereNotNull('p.min_stock')
            ->where('p.min_stock', '>', 0)
            ->groupBy('p.id', 'p.code', 'p.name', 'p.min_stock')
            ->havingRaw('SUM(s.quantity) <= p.min_stock')
            ->orderBy('p.name')
            ->get();

        // ── Cảnh báo: hàng sắp hết hạn ───────────────────────────────
        $expiringSoonItems = DB::table('lots as l')
            ->join('products as p', 'l.product_id', '=', 'p.id')
            ->leftJoin('stock as s', 's.lot_id', '=', 'l.id')
            ->select('l.lot_number', 'l.expiry_date', 'p.name as product_name')
            ->selectRaw('COALESCE(SUM(s.quantity), 0) as quantity')
            ->where('l.status', 1)
            ->whereNotNull('l.expiry_date')
            ->where('l.expiry_date', '<=', now()->addDays(30)->toDateString())
            ->groupBy('l.id', 'l.lot_number', 'l.expiry_date', 'p.name')
            ->orderBy('l.expiry_date')
            ->get();

        return view('reports.index', compact(
            'categories', 'products',
            'totalReceiptQty', 'totalIssueQty',
            'totalReceiptVouchers', 'totalIssueVouchers',
            'openingStock', 'closingStock',
            'lowStockCount', 'expiringSoonCount',
            'chartLabels', 'chartReceipts', 'chartIssues',
            'purposeLabels', 'purposeData',
            'reportRows',
            'lowStockItems', 'expiringSoonItems'
        ));
    }

    public function exportExcel(Request $request)
    {
        // TODO: implement với maatwebsite/excel
        // return Excel::download(new ReportExport($request), 'bao-cao-tong-hop.xlsx');
        abort(501, 'Chức năng xuất Excel đang được phát triển.');
    }

    public function exportPdf(Request $request)
    {
        // TODO: implement với barryvdh/laravel-dompdf
        // $data = ...; return PDF::loadView('reports.pdf', $data)->download('bao-cao.pdf');
        abort(501, 'Chức năng xuất PDF đang được phát triển.');
    }
}