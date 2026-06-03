<?php

namespace App\Http\Controllers;

use App\Exports\AlertBelowMinExport;
use App\Exports\AlertSlowMovingExport;
use App\Exports\AlertNearExpiryExport;
use App\Models\Category;
use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportAlertController extends Controller
{
    // ══════════════════════════════════════════════════════════════════════════
    // 1. HÀNG DƯỚI ĐỊNH MỨC
    // ══════════════════════════════════════════════════════════════════════════

    public function belowMin(Request $request)
    {
        [$items, $summary] = $this->fetchBelowMin($request);

        $categories = Category::where('status', 1)->orderBy('name')->get();
        $locations  = Location::orderBy('name')->get();

        return view('reports.alerts.below_min', compact(
            'items', 'summary', 'categories', 'locations'
        ));
    }

    public function belowMinExportExcel(Request $request)
    {
        [$items] = $this->fetchBelowMin($request);
        $filename = 'canh-bao-duoi-dinh-muc_' . now()->format('Ymd_His') . '.xlsx';
        return Excel::download(new AlertBelowMinExport($items), $filename);
    }

    public function belowMinExportPdf(Request $request)
    {
        [$items, $summary] = $this->fetchBelowMin($request);

        $pdf = Pdf::loadView('reports.alerts.pdf.below_min', compact('items', 'summary'))
            ->setPaper('a4', 'landscape')
            ->setOptions([
                'defaultFont'          => 'DejaVu Sans',
                'isRemoteEnabled'      => false,
                'isHtml5ParserEnabled' => true,
            ]);

        return $pdf->download('canh-bao-duoi-dinh-muc_' . now()->format('Ymd') . '.pdf');
    }

    private function fetchBelowMin(Request $request): array
    {
        $query = DB::table('reorder_rules as rr')
            ->join('products as p',   'p.id',  '=', 'rr.product_id')
            ->join('locations as l',  'l.id',  '=', 'rr.location_id')
            ->join('uoms as u',       'u.id',  '=', 'p.uom_id')
            ->leftJoin('categories as c', 'c.id', '=', 'p.category_id')
            ->leftJoinSub(
                DB::table('stock')
                    ->selectRaw('product_id, location_id, SUM(available_qty) AS avail_qty, SUM(quantity) AS total_qty')
                    ->groupBy('product_id', 'location_id'),
                'st',
                fn($j) => $j->on('st.product_id',  '=', 'rr.product_id')
                             ->on('st.location_id', '=', 'rr.location_id')
            )
            ->select(
                'p.id as product_id',
                'p.code as product_code',
                'p.name as product_name',
                'c.name as category_name',
                'u.name as uom_name',
                'l.code as location_code',
                'l.name as location_name',
                'rr.min_qty',
                'rr.max_qty',
                DB::raw('COALESCE(st.avail_qty, 0) AS current_qty'),
                DB::raw('rr.min_qty - COALESCE(st.avail_qty, 0) AS shortage_qty'),
                DB::raw('CASE WHEN rr.max_qty > 0 THEN rr.max_qty - COALESCE(st.avail_qty, 0) ELSE NULL END AS order_qty'),
            )
            ->where('rr.status', 1)
            ->where('p.status',  1)
            ->whereRaw('COALESCE(st.avail_qty, 0) < rr.min_qty');

        if ($request->filled('category_id')) {
            $query->where('p.category_id', $request->category_id);
        }
        if ($request->filled('location_id')) {
            $query->where('rr.location_id', $request->location_id);
        }

        $items = $query->orderByRaw('(rr.min_qty - COALESCE(st.avail_qty, 0)) DESC')
                       ->get();

        $summary = [
            'total'       => $items->count(),
            'zero_stock'  => $items->where('current_qty', '<=', 0)->count(),
            'total_shortage' => $items->sum('shortage_qty'),
        ];

        return [$items, $summary];
    }

    // ══════════════════════════════════════════════════════════════════════════
    // 2. HÀNG ĐỌNG KHO LÂU NGÀY (slow-moving)
    // ══════════════════════════════════════════════════════════════════════════

    public function slowMoving(Request $request)
    {
        $days = (int) ($request->days ?? 90);
        [$items, $summary] = $this->fetchSlowMoving($request, $days);

        $categories = Category::where('status', 1)->orderBy('name')->get();
        $locations  = Location::orderBy('name')->get();

        return view('reports.alerts.slow_moving', compact(
            'items', 'summary', 'categories', 'locations', 'days'
        ));
    }

    public function slowMovingExportExcel(Request $request)
    {
        $days = (int) ($request->days ?? 90);
        [$items] = $this->fetchSlowMoving($request, $days);
        $filename = "canh-bao-hang-dong-kho_{$days}ngay_" . now()->format('Ymd_His') . '.xlsx';
        return Excel::download(new AlertSlowMovingExport($items, $days), $filename);
    }

    public function slowMovingExportPdf(Request $request)
    {
        $days = (int) ($request->days ?? 90);
        [$items, $summary] = $this->fetchSlowMoving($request, $days);

        $pdf = Pdf::loadView('reports.alerts.pdf.slow_moving', compact('items', 'summary', 'days'))
            ->setPaper('a4', 'landscape')
            ->setOptions([
                'defaultFont'          => 'DejaVu Sans',
                'isRemoteEnabled'      => false,
                'isHtml5ParserEnabled' => true,
            ]);

        return $pdf->download("canh-bao-hang-dong-kho_{$days}ngay_" . now()->format('Ymd') . '.pdf');
    }

    private function fetchSlowMoving(Request $request, int $days): array
    {
        $cutoffDate = now()->subDays($days)->toDateTimeString();

        // Lấy lần xuất cuối của mỗi product × location từ stock_ledger
        $lastIssueSub = DB::table('stock_ledger')
            ->selectRaw('product_id, location_id, MAX(transaction_date) AS last_issue_date')
            ->where('direction', 2)
            ->groupBy('product_id', 'location_id');

        $query = DB::table('stock as s')
            ->join('products as p',   'p.id',  '=', 's.product_id')
            ->join('locations as l',  'l.id',  '=', 's.location_id')
            ->join('uoms as u',       'u.id',  '=', 'p.uom_id')
            ->leftJoin('categories as c', 'c.id', '=', 'p.category_id')
            ->leftJoinSub($lastIssueSub, 'li',
                fn($j) => $j->on('li.product_id',  '=', 's.product_id')
                             ->on('li.location_id', '=', 's.location_id')
            )
            ->selectRaw("
                p.id AS product_id,
                p.code AS product_code,
                p.name AS product_name,
                c.name AS category_name,
                u.name AS uom_name,
                l.code AS location_code,
                l.name AS location_name,
                SUM(s.quantity)     AS total_qty,
                SUM(s.reserved_qty) AS reserved_qty,
                SUM(s.available_qty) AS available_qty,
                MAX(s.received_date)  AS last_received_date,
                MAX(li.last_issue_date) AS last_issue_date,
                DATEDIFF(day, COALESCE(MAX(li.last_issue_date), MAX(s.received_date)), GETDATE()) AS idle_days
            ")
            ->where('p.status', 1)
            ->where('s.quantity', '>', 0)
            ->whereRaw("COALESCE(li.last_issue_date, s.received_date) < ?", [$cutoffDate])
            ->groupBy(
                'p.id', 'p.code', 'p.name',
                'c.name', 'u.name',
                'l.code', 'l.name'
            );

        if ($request->filled('category_id')) {
            $query->where('p.category_id', $request->category_id);
        }
        if ($request->filled('location_id')) {
            $query->where('s.location_id', $request->location_id);
        }

        $items = $query->orderByRaw('idle_days DESC')
                       ->get();

        $summary = [
            'total'     => $items->count(),
            'total_qty' => $items->sum('total_qty'),
            'avg_idle'  => $items->count() ? round($items->avg('idle_days')) : 0,
            'max_idle'  => $items->max('idle_days') ?? 0,
        ];

        return [$items, $summary];
    }

    // ══════════════════════════════════════════════════════════════════════════
    // 3. HÀNG CẬN DATE
    // ══════════════════════════════════════════════════════════════════════════

    public function nearExpiry(Request $request)
    {
        $days = (int) ($request->days ?? 30);
        [$items, $summary] = $this->fetchNearExpiry($request, $days);

        $categories = Category::where('status', 1)->orderBy('name')->get();
        $locations  = Location::orderBy('name')->get();

        return view('reports.alerts.near_expiry', compact(
            'items', 'summary', 'categories', 'locations', 'days'
        ));
    }

    public function nearExpiryExportExcel(Request $request)
    {
        $days = (int) ($request->days ?? 30);
        [$items] = $this->fetchNearExpiry($request, $days);
        $filename = "canh-bao-can-date_{$days}ngay_" . now()->format('Ymd_His') . '.xlsx';
        return Excel::download(new AlertNearExpiryExport($items, $days), $filename);
    }

    public function nearExpiryExportPdf(Request $request)
    {
        $days = (int) ($request->days ?? 30);
        [$items, $summary] = $this->fetchNearExpiry($request, $days);

        $pdf = Pdf::loadView('reports.alerts.pdf.near_expiry', compact('items', 'summary', 'days'))
            ->setPaper('a4', 'landscape')
            ->setOptions([
                'defaultFont'          => 'DejaVu Sans',
                'isRemoteEnabled'      => false,
                'isHtml5ParserEnabled' => true,
            ]);

        return $pdf->download("canh-bao-can-date_{$days}ngay_" . now()->format('Ymd') . '.pdf');
    }

    private function fetchNearExpiry(Request $request, int $days): array
    {
        $today   = now()->toDateString();
        $horizon = now()->addDays($days)->toDateString();

        // Lấy từ bảng lots (hàng có tracking lot) và từ stock.expiry_date (hàng thường)
        $fromLots = DB::table('lots as lt')
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
            ->join('locations as l', 'l.id', '=', 'st.location_id')
            ->selectRaw("
                p.id AS product_id,
                p.code AS product_code,
                p.name AS product_name,
                c.name AS category_name,
                u.name AS uom_name,
                lt.lot_number,
                lt.expiry_date,
                l.code AS location_code,
                l.name AS location_name,
                st.qty AS quantity,
                DATEDIFF(day, GETDATE(), lt.expiry_date) AS days_left,
                'lot' AS source
            ")
            ->where('lt.status', 1)
            ->whereNotNull('lt.expiry_date')
            ->whereBetween('lt.expiry_date', [$today, $horizon])
            ->where('st.qty', '>', 0);

        $fromStock = DB::table('stock as s')
            ->join('products as p',   'p.id', '=', 's.product_id')
            ->join('locations as l',  'l.id', '=', 's.location_id')
            ->join('uoms as u',       'u.id', '=', 'p.uom_id')
            ->leftJoin('categories as c', 'c.id', '=', 'p.category_id')
            ->selectRaw("
                p.id AS product_id,
                p.code AS product_code,
                p.name AS product_name,
                c.name AS category_name,
                u.name AS uom_name,
                NULL AS lot_number,
                s.expiry_date,
                l.code AS location_code,
                l.name AS location_name,
                SUM(s.quantity) AS quantity,
                DATEDIFF(day, GETDATE(), s.expiry_date) AS days_left,
                'stock' AS source
            ")
            ->whereNull('s.lot_id')
            ->whereNotNull('s.expiry_date')
            ->whereBetween('s.expiry_date', [$today, $horizon])
            ->where('s.quantity', '>', 0)
            ->groupBy(
                'p.id', 'p.code', 'p.name',
                'c.name', 'u.name',
                's.expiry_date', 'l.code', 'l.name'
            );

        // Áp filter
        if ($request->filled('category_id')) {
            $fromLots->where('p.category_id',  $request->category_id);
            $fromStock->where('p.category_id', $request->category_id);
        }
        if ($request->filled('location_id')) {
            $fromLots->where('st.location_id',  $request->location_id);
            $fromStock->where('s.location_id', $request->location_id);
        }

        $items = $fromLots->union($fromStock)
                          ->orderBy('days_left')
                          ->get();

        $expired     = $items->where('days_left', '<', 0)->count();   // expiry_date = today edge
        $within7     = $items->whereBetween('days_left', [0, 7])->count();
        $within30    = $items->count();

        $summary = [
            'total'       => $within30,
            'expired'     => $expired,
            'within_7'    => $within7,
            'total_qty'   => $items->sum('quantity'),
        ];

        return [$items, $summary];
    }
}