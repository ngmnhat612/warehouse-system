<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Location;
use App\Models\Product;
use App\Models\Stock;
use App\Models\StockLedger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class InventoryController extends Controller
{
    // ──────────────────────────────────────────────────────────────────────────
    // TỒNG TỒN KHO HIỆN TẠI
    // ──────────────────────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        // ── Filter lists ──────────────────────────────────────────────────────
        $categories = Category::where('status', 1)->orderBy('name')->get();
        $locations  = Location::where('status', 1)->where('type', 1)->orderBy('code')->get(); // Internal only
        $products   = Product::where('status', 1)->orderBy('name')->get();

        // ── KPI cards ─────────────────────────────────────────────────────────
        $totalSkuCount   = DB::table('stock')->distinct('product_id')->count('product_id');
        $totalQty        = DB::table('stock')->sum('quantity');
        $reservedQty     = DB::table('stock')->sum('reserved_qty');
        $quarantineCount = DB::table('stock')->where('status', Stock::STATUS_QUARANTINE)->sum('quantity');

        // ── Main query: tồn kho theo sản phẩm + vị trí ───────────────────────
        $query = DB::table('stock as s')
            ->join('products as p',   's.product_id',   '=', 'p.id')
            ->join('locations as l',  's.location_id',  '=', 'l.id')
            ->join('uoms as u',       'p.uom_id',       '=', 'u.id')
            ->leftJoin('categories as c', 'p.category_id', '=', 'c.id')
            ->leftJoin('lots as lt',  's.lot_id',       '=', 'lt.id')
            ->leftJoin('serials as sr', 's.serial_id',  '=', 'sr.id')
            ->select(
                's.id',
                'p.code as product_code',
                'p.name as product_name',
                'p.min_stock',
                'p.max_stock',
                'p.tracking_type',
                'c.name as category_name',
                'l.code as location_code',
                'l.name as location_name',
                'l.type as location_type',
                'u.name as uom_name',
                's.quantity',
                's.reserved_qty',
                DB::raw('(s.quantity - s.reserved_qty) as available_qty'),
                's.status',
                's.expiry_date',
                's.lot_id',
                's.serial_id',
                'lt.lot_number',
                'sr.serial_number',
                's.updated_at',
            )
            ->where('p.status', 1);

        // Filters
        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(function ($sub) use ($q) {
                $sub->where('p.name', 'like', "%{$q}%")
                    ->orWhere('p.code', 'like', "%{$q}%")
                    ->orWhere('lt.lot_number',    'like', "%{$q}%")
                    ->orWhere('sr.serial_number', 'like', "%{$q}%");
            });
        }

        if ($request->filled('category_id')) {
            $query->where('p.category_id', $request->category_id);
        }

        if ($request->filled('location_id')) {
            $query->where('s.location_id', $request->location_id);
        }

        if ($request->filled('product_id')) {
            $query->where('p.id', $request->product_id);
        }

        if ($request->filled('status')) {
            $query->where('s.status', $request->status);
        }

        // Chỉ hiển thị tồn kho > 0 mặc định, trừ khi lọc cụ thể
        if (!$request->filled('show_zero')) {
            $query->where('s.quantity', '>', 0);
        }

        $stocks = $query->orderBy('p.code')->orderBy('l.code')
            ->paginate(25)
            ->withQueryString();

        return view('inventory.index', compact(
            'stocks',
            'categories', 'locations', 'products',
            'totalSkuCount', 'totalQty', 'reservedQty', 'quarantineCount'
        ));
    }

    // ──────────────────────────────────────────────────────────────────────────
    // LỊCH SỬ GIAO DỊCH (Stock Ledger)
    // ──────────────────────────────────────────────────────────────────────────
    public function ledger(Request $request)
    {
        $products  = Product::where('status', 1)->orderBy('name')->get();
        $locations = Location::where('status', 1)->where('type', 1)->orderBy('code')->get();
        $users     = DB::table('users')->select('id', 'name')->orderBy('name')->get();

        $transactionTypes = [
            'RECEIPT'   => 'Nhập kho',
            'ISSUE'     => 'Xuất kho',
            'TRANSFER'  => 'Chuyển kho',
            'SCRAP'     => 'Hủy hàng',
            'ADJUST'    => 'Điều chỉnh',
            'TRANSFORM' => 'Tách/Ghép',
            'RETURN'    => 'Trả hàng',
        ];

        $dateFrom = $request->date_from ?? now()->startOfMonth()->toDateString();
        $dateTo   = $request->date_to   ?? now()->toDateString();

        $query = DB::table('stock_ledger as sl')
            ->join('products as p',    'sl.product_id',  '=', 'p.id')
            ->join('locations as l',   'sl.location_id', '=', 'l.id')
            ->join('uoms as u',        'p.uom_id',       '=', 'u.id')
            ->leftJoin('lots as lt',   'sl.lot_id',      '=', 'lt.id')
            ->leftJoin('serials as sr','sl.serial_id',   '=', 'sr.id')
            ->leftJoin('users as usr', 'sl.created_by',  '=', 'usr.id')
            ->select(
                'sl.id',
                'sl.transaction_type',
                'sl.reference_code',
                'sl.reference_type',
                'sl.direction',
                'sl.quantity',
                'sl.balance_after',
                'sl.transaction_date',
                'sl.note',
                'p.code as product_code',
                'p.name as product_name',
                'l.code as location_code',
                'l.name as location_name',
                'u.name as uom_name',
                'lt.lot_number',
                'sr.serial_number',
                'usr.name as created_by_name',
            )
            ->whereBetween('sl.transaction_date', [$dateFrom, $dateTo . ' 23:59:59']);

        if ($request->filled('product_id')) {
            $query->where('sl.product_id', $request->product_id);
        }
        if ($request->filled('location_id')) {
            $query->where('sl.location_id', $request->location_id);
        }
        if ($request->filled('transaction_type')) {
            $query->where('sl.transaction_type', $request->transaction_type);
        }
        if ($request->filled('direction')) {
            $query->where('sl.direction', $request->direction);
        }
        if ($request->filled('causer_id')) {
            $query->where('sl.created_by', $request->causer_id);
        }
        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(function ($sub) use ($q) {
                $sub->where('sl.reference_code',  'like', "%{$q}%")
                    ->orWhere('p.name',            'like', "%{$q}%")
                    ->orWhere('p.code',            'like', "%{$q}%")
                    ->orWhere('lt.lot_number',     'like', "%{$q}%")
                    ->orWhere('sr.serial_number',  'like', "%{$q}%");
            });
        }

        // KPI — clone TRƯỚC khi paginate
        $totalIn  = (clone $query)->where('sl.direction', 1)->sum('sl.quantity');
        $totalOut = (clone $query)->where('sl.direction', 2)->sum('sl.quantity');

        $ledgers = $query->orderByDesc('sl.transaction_date')->orderByDesc('sl.id')
            ->paginate(30)
            ->withQueryString();

        return view('inventory.ledger', compact(
            'ledgers', 'products', 'locations', 'users', 'transactionTypes',
            'dateFrom', 'dateTo', 'totalIn', 'totalOut'
        ));
    }

    public function exportLedger(Request $request)
    {
        $filters = $request->only([
            'date_from', 'date_to',
            'product_id', 'location_id',
            'transaction_type', 'direction',
            'causer_id', 'search',
        ]);

        $dateFrom = $filters['date_from'] ?? now()->startOfMonth()->toDateString();
        $dateTo   = $filters['date_to']   ?? now()->toDateString();
        $filename = "the-kho_{$dateFrom}_{$dateTo}.xlsx";

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\StockLedgerExport($filters),
            $filename
        );
    }

    // ──────────────────────────────────────────────────────────────────────────
    // VỊ TRÍ KHO — tổng quan tồn kho theo từng vị trí
    // ──────────────────────────────────────────────────────────────────────────

    public function locations(Request $request)
    {
        // ── Tổng hợp tồn kho theo location ───────────────────────────────────
        $locQuery = DB::table('locations as l')
            ->leftJoin('stock as s', function ($join) {
                $join->on('s.location_id', '=', 'l.id')
                     ->where('s.quantity', '>', 0);
            })
            ->select(
                'l.id',
                'l.code',
                'l.name',
                'l.type',
                'l.parent_id',
                'l.capacity_limit',
                'l.status',
                DB::raw('COUNT(DISTINCT s.product_id)  as sku_count'),
                DB::raw('COALESCE(SUM(s.quantity), 0)  as total_qty'),
                DB::raw('COALESCE(SUM(s.reserved_qty), 0) as reserved_qty'),
                DB::raw('COALESCE(SUM(s.quantity - s.reserved_qty), 0) as available_qty'),
            )
            ->groupBy('l.id', 'l.code', 'l.name', 'l.type', 'l.parent_id', 'l.capacity_limit', 'l.status');

        // Chỉ hiện Internal mặc định (vị trí thực), trừ khi chọn lọc loại khác
        if ($request->filled('type')) {
            $locQuery->where('l.type', $request->type);
        } else {
            $locQuery->where('l.type', Location::TYPE_INTERNAL);
        }

        if ($request->filled('search')) {
            $q = $request->search;
            $locQuery->where(function ($sub) use ($q) {
                $sub->where('l.name', 'like', "%{$q}%")
                    ->orWhere('l.code', 'like', "%{$q}%");
            });
        }

        if ($request->filled('status')) {
            $locQuery->where('l.status', $request->status);
        } else {
            $locQuery->where('l.status', 1);
        }

        $locationRows = $locQuery->orderBy('l.code')->get();

        // ── KPI cards ─────────────────────────────────────────────────────────
        $totalLocations = $locationRows->count();
        $occupiedCount  = $locationRows->where('sku_count', '>', 0)->count();
        $emptyCount     = $locationRows->where('sku_count', 0)->count();
        $overCapacity   = $locationRows->filter(fn($r) =>
            $r->capacity_limit > 0 && $r->total_qty > $r->capacity_limit
        )->count();

        return view('inventory.locations', compact(
            'locationRows',
            'totalLocations', 'occupiedCount', 'emptyCount', 'overCapacity'
        ));
    }
}