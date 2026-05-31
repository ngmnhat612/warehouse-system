<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Models\Activity;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        // ── Danh sách người dùng cho filter ───────────────────────────
        $users = DB::table('users')->select('id', 'name')->orderBy('name')->get();

        // ── Danh sách loại đối tượng đã có trong log ──────────────────
        $subjectTypes = Activity::query()
            ->whereNotNull('subject_type')
            ->distinct()
            ->pluck('subject_type')
            ->mapWithKeys(function ($class) {
                $parts = explode('\\', $class);
                $label = match (end($parts)) {
                    'Product'         => 'Hàng hóa',
                    'Category'        => 'Nhóm hàng',
                    'Supplier'        => 'Nhà cung cấp',
                    'Location'        => 'Vị trí kho',
                    'Uom'             => 'Đơn vị tính',
                    'UomConversion'   => 'Quy đổi ĐVT',
                    'Employee'        => 'Nhân viên',
                    'Bom'             => 'BOM',
                    'PutawayRule'     => 'Putaway Rule',
                    'ReorderRule'     => 'Reorder Rule',
                    'StockReceipt'    => 'Phiếu nhập',
                    'StockIssue'      => 'Phiếu xuất',
                    'StockTransfer'   => 'Phiếu chuyển kho',
                    'Scrap'           => 'Phiếu hủy',
                    'InventoryCheck'  => 'Phiếu kiểm kê',
                    'StockAdjustment' => 'Điều chỉnh tồn kho',
                    default           => end($parts),
                };
                return [$class => $label];
            })
            ->toArray();

        // ── KPI cards (hôm nay) ────────────────────────────────────────
        $totalToday      = Activity::whereDate('created_at', today())->count();
        $totalUpdated    = Activity::whereDate('created_at', today())->where('event', 'updated')->count();
        $totalDeleted    = Activity::whereDate('created_at', today())
                              ->whereIn('event', ['deleted', 'cancelled'])->count();
        $activeUsersToday = Activity::whereDate('created_at', today())
                              ->whereNotNull('causer_id')
                              ->distinct('causer_id')
                              ->count('causer_id');

        // ── Query danh sách log ────────────────────────────────────────
        $query = Activity::with('causer')
            ->when($request->date_from, fn($q) => $q->whereDate('created_at', '>=', $request->date_from))
            ->when($request->date_to,   fn($q) => $q->whereDate('created_at', '<=', $request->date_to))
            ->when($request->causer_id, fn($q) => $q->where('causer_id', $request->causer_id))
            ->when($request->subject_type, fn($q) => $q->where('subject_type', $request->subject_type))
            ->when($request->event,     fn($q) => $q->where('event', $request->event))
            ->when($request->search,    fn($q) => $q->where('description', 'like', "%{$request->search}%"))
            ->orderByDesc('created_at');

        $activities = $query->paginate(25)->withQueryString();

        return view('activity-log.index', compact(
            'activities',
            'users',
            'subjectTypes',
            'totalToday',
            'totalUpdated',
            'totalDeleted',
            'activeUsersToday'
        ));
    }

    public function export(Request $request)
    {
        // TODO: implement với maatwebsite/excel
        abort(501, 'Chức năng xuất Excel đang được phát triển.');
    }
}