<?php

use App\Http\Controllers\ScrapController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Master\UomController;
use App\Http\Controllers\Master\CategoryController;
use App\Http\Controllers\Master\ProductController;
use App\Http\Controllers\Master\SupplierController;
use App\Http\Controllers\Master\LocationController;
use App\Http\Controllers\Master\UomConversionController;
use App\Http\Controllers\Master\EmployeeController;
use App\Http\Controllers\Master\BomController;
use App\Http\Controllers\Master\PutawayRuleController;
use App\Http\Controllers\Master\ReorderRuleController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\StockReceiptController;
use App\Http\Controllers\StockIssueController;
use App\Http\Controllers\StockTransferController;
use App\Http\Controllers\InventoryCheckController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\ReportAlertController;
use App\Http\Controllers\StockTransformationController;
use Illuminate\Support\Facades\Route;



Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::prefix('master')->name('master.')->group(function () {

        // Đơn vị tính
        Route::resource('uom', UomController::class)
            ->only(['index', 'store', 'update', 'destroy'])
            ->names([
                'index'   => 'uom.index',
                'store'   => 'uom.store',
                'update'  => 'uom.update',
                'destroy' => 'uom.destroy',
            ]);


        Route::resource('category', CategoryController::class)
            ->only(['index', 'store', 'update', 'destroy'])
            ->names([
                'index'   => 'category.index',
                'store'   => 'category.store',
                'update'  => 'category.update',
                'destroy' => 'category.destroy',
            ]);

        Route::get('product/generate-barcode', [ProductController::class, 'generateBarcode'])
            ->name('product.generate-barcode');

        Route::resource('product', ProductController::class)
            ->only(['index', 'store', 'update', 'destroy'])
            ->names([
                'index'   => 'product.index',
                'store'   => 'product.store',
                'update'  => 'product.update',
                'destroy' => 'product.destroy',
            ]);

        Route::resource('supplier', SupplierController::class)
            ->only(['index', 'store', 'update', 'destroy'])
            ->names([
                'index'   => 'supplier.index',
                'store'   => 'supplier.store',
                'update'  => 'supplier.update',
                'destroy' => 'supplier.destroy',
            ]);

        Route::get('location/{location}/barcode', [LocationController::class, 'barcode'])
            ->name('location.barcode');

        Route::resource('location', LocationController::class)
            ->only(['index', 'store', 'update', 'destroy'])
            ->names([
                'index'   => 'location.index',
                'store'   => 'location.store',
                'update'  => 'location.update',
                'destroy' => 'location.destroy',
            ]);

        Route::resource('uom_conversion', UomConversionController::class)
            ->only(['index', 'store', 'update', 'destroy'])
            ->names([
                'index'   => 'uom_conversion.index',
                'store'   => 'uom_conversion.store',
                'update'  => 'uom_conversion.update',
                'destroy' => 'uom_conversion.destroy',
            ]);

        // Nhân viên
        Route::resource('employee', EmployeeController::class)
            ->only(['index', 'store', 'update', 'destroy'])
            ->names([
                'index'   => 'employee.index',
                'store'   => 'employee.store',
                'update'  => 'employee.update',
                'destroy' => 'employee.destroy',
            ]);
        // Quản lý tài khoản của nhân viên
        Route::post('employee/{employee}/account',        [EmployeeController::class, 'createAccount'])->name('employee.account.create');
        Route::put('employee/{employee}/account',         [EmployeeController::class, 'updateAccount'])->name('employee.account.update');
        Route::delete('employee/{employee}/account',      [EmployeeController::class, 'deleteAccount'])->name('employee.account.delete');

        Route::post('employee/user/{user}/activate', [EmployeeController::class, 'activateUser'])
            ->name('employee.user.activate');

        Route::delete('employee/user/{user}/reject', [EmployeeController::class, 'rejectUser'])
            ->name('employee.user.reject');

        Route::resource('bom', BomController::class)
            ->only(['index', 'create', 'store', 'edit', 'update', 'destroy'])
            ->names([
                'index'   => 'bom.index',
                'create'  => 'bom.create',
                'store'   => 'bom.store',
                'edit'    => 'bom.edit',
                'update'  => 'bom.update',
                'destroy' => 'bom.destroy',
            ]);

        // Putaway Rules — quy tắc gán vị trí tự động khi nhập kho
        Route::resource('putaway_rule', PutawayRuleController::class)
            ->only(['index', 'store', 'update', 'destroy'])
            ->names([
                'index'   => 'putaway_rule.index',
                'store'   => 'putaway_rule.store',
                'update'  => 'putaway_rule.update',
                'destroy' => 'putaway_rule.destroy',
            ]);

        // Reorder Rules — quy tắc cảnh báo / tái đặt hàng
        Route::resource('reorder_rule', ReorderRuleController::class)
            ->only(['index', 'store', 'update', 'destroy'])
            ->names([
                'index'   => 'reorder_rule.index',
                'store'   => 'reorder_rule.store',
                'update'  => 'reorder_rule.update',
                'destroy' => 'reorder_rule.destroy',
            ]);

});

    // ── BÁO CÁO ───────────────────────────────────────────────────────
    Route::prefix('reports')->name('reports.')->group(function () {
    Route::get('/',             [ReportController::class, 'index'])->name('index');
    Route::get('/export/excel', [ReportController::class, 'exportExcel'])->name('export.excel');
    Route::get('/export/pdf',   [ReportController::class, 'exportPdf'])->name('export.pdf');

    // ── Cảnh báo rủi ro ──────────────────────────────────────────────
    Route::get('/alerts/below-min',                [ReportAlertController::class, 'belowMin'])->name('alerts.below_min');
    Route::get('/alerts/below-min/export/excel',   [ReportAlertController::class, 'belowMinExportExcel'])->name('alerts.below_min.excel');
    Route::get('/alerts/below-min/export/pdf',     [ReportAlertController::class, 'belowMinExportPdf'])->name('alerts.below_min.pdf');

    Route::get('/alerts/slow-moving',              [ReportAlertController::class, 'slowMoving'])->name('alerts.slow_moving');
    Route::get('/alerts/slow-moving/export/excel', [ReportAlertController::class, 'slowMovingExportExcel'])->name('alerts.slow_moving.excel');
    Route::get('/alerts/slow-moving/export/pdf',   [ReportAlertController::class, 'slowMovingExportPdf'])->name('alerts.slow_moving.pdf');

    Route::get('/alerts/near-expiry',              [ReportAlertController::class, 'nearExpiry'])->name('alerts.near_expiry');
    Route::get('/alerts/near-expiry/export/excel', [ReportAlertController::class, 'nearExpiryExportExcel'])->name('alerts.near_expiry.excel');
    Route::get('/alerts/near-expiry/export/pdf',   [ReportAlertController::class, 'nearExpiryExportPdf'])->name('alerts.near_expiry.pdf');
    });

    // ── NHẬT KÝ HỆ THỐNG ──────────────────────────────────────────────
    Route::prefix('activity-log')->name('activity-log.')->group(function () {
        Route::get('/',        [ActivityLogController::class, 'index'])->name('index');
        Route::get('/export',  [ActivityLogController::class, 'export'])->name('export');
    });

    // ── NHẬP KHO ───────────────────────────────────────────────────────
    Route::get('receipts/suggest-putaway',    [StockReceiptController::class, 'suggestPutaway'])->name('receipts.suggest-putaway');
    Route::resource('receipts', StockReceiptController::class);
    Route::post('receipts/{receipt}/submit',  [StockReceiptController::class, 'submit'])->name('receipts.submit');
    Route::post('receipts/{receipt}/approve', [StockReceiptController::class, 'approve'])->name('receipts.approve');
    Route::post('receipts/{receipt}/confirm', [StockReceiptController::class, 'confirm'])->name('receipts.confirm');
    Route::post('receipts/{receipt}/cancel',  [StockReceiptController::class, 'cancel'])->name('receipts.cancel');

    // ── XUẤT KHO ───────────────────────────────────────────────────────
    Route::get('issues/stock-locations/{productId}', [StockIssueController::class, 'stockLocations'])
    ->name('issues.stockLocations');
    Route::resource('issues', StockIssueController::class);
    Route::post('issues/{issue}/submit',  [StockIssueController::class, 'submit'])->name('issues.submit');
    Route::post('issues/{issue}/approve', [StockIssueController::class, 'approve'])->name('issues.approve');
    Route::post('issues/{issue}/confirm', [StockIssueController::class, 'confirm'])->name('issues.confirm');
    Route::post('issues/{issue}/cancel',  [StockIssueController::class, 'cancel'])->name('issues.cancel');

    // ── CHUYỂN KHO ───────────────────────────────────────────────────────
    Route::get('transfers/stock-locations', [StockTransferController::class, 'stockLocations'])->name('transfers.stock-locations');
    Route::resource('transfers', StockTransferController::class);
    Route::post('transfers/{transfer}/submit',  [StockTransferController::class, 'submit'])->name('transfers.submit');
    Route::post('transfers/{transfer}/approve', [StockTransferController::class, 'approve'])->name('transfers.approve');
    Route::post('transfers/{transfer}/confirm', [StockTransferController::class, 'confirm'])->name('transfers.confirm');
    Route::post('transfers/{transfer}/cancel',  [StockTransferController::class, 'cancel'])->name('transfers.cancel');

    // ── HỦY HÀNG ───────────────────────────────────────────────────────
    Route::get('scraps/stock-locations/{productId}', [ScrapController::class, 'stockLocations'])->name('scraps.stock-locations');
    Route::resource('scraps', ScrapController::class);
    Route::post('scraps/{scrap}/submit',  [ScrapController::class, 'submit'])->name('scraps.submit');
    Route::post('scraps/{scrap}/approve', [ScrapController::class, 'approve'])->name('scraps.approve');
    Route::post('scraps/{scrap}/confirm', [ScrapController::class, 'confirm'])->name('scraps.confirm');
    Route::post('scraps/{scrap}/cancel',  [ScrapController::class, 'cancel'])->name('scraps.cancel');
    Route::get('scraps/{scrap}/print',    [ScrapController::class, 'print'])->name('scraps.print');

    // ── KIỂM KÊ ───────────────────────────────────────────────────────
    Route::resource('stocktakes', InventoryCheckController::class);
    Route::post('stocktakes/{stocktake}/activate',  [InventoryCheckController::class, 'activate'])->name('stocktakes.activate');
    Route::post('stocktakes/{stocktake}/complete',  [InventoryCheckController::class, 'complete'])->name('stocktakes.complete');
    Route::post('stocktakes/{stocktake}/unfreeze',  [InventoryCheckController::class, 'unfreeze'])->name('stocktakes.unfreeze');
    Route::delete('stocktakes/{stocktake}/cancel',  [InventoryCheckController::class, 'cancel'])->name('stocktakes.cancel');

    // ── KIỂM KÊ ──────────────────────────────────────
    // Lines — cập nhật hàng loạt
    Route::post('stocktakes/{stocktake}/lines',     [InventoryCheckController::class, 'updateLines'])->name('stocktakes.lines.update');

    // Điều chỉnh tồn kho
    Route::post('stocktakes/{stocktake}/adjustment',              [InventoryCheckController::class, 'createAdjustment'])->name('stocktakes.adjustment.create');
    Route::get('stocktakes/{stocktake}/adjustment/{adjustment}',  [InventoryCheckController::class, 'showAdjustment'])->name('stocktakes.adjustment.show');
    Route::post('stocktakes/{stocktake}/adjustment/{adjustment}/apply', [InventoryCheckController::class, 'applyAdjustment'])->name('stocktakes.adjustment.apply');

    Route::get('stocktakes/{stocktake}/export/excel',  [InventoryCheckController::class, 'downloadTemplate'])->name('stocktakes.export.excel');
    Route::get('stocktakes/{stocktake}/export/pdf',    [InventoryCheckController::class, 'exportPdf'])->name('stocktakes.export.pdf');
    Route::post('stocktakes/{stocktake}/import/excel', [InventoryCheckController::class, 'importExcel'])->name('stocktakes.import.excel');

    // ── TÁCH / GHÉP HÀNG HÓA ──────────────────────────────────────
    Route::get('transformations/locations-by-product/{product}', [StockTransformationController::class, 'locationsByProduct'])->name('transformations.locations-by-product');
    Route::resource('transformations', StockTransformationController::class);
    Route::post('transformations/{transformation}/submit',  [StockTransformationController::class, 'submit'])->name('transformations.submit');
    Route::post('transformations/{transformation}/approve', [StockTransformationController::class, 'approve'])->name('transformations.approve');
    Route::post('transformations/{transformation}/confirm', [StockTransformationController::class, 'confirm'])->name('transformations.confirm');
    Route::post('transformations/{transformation}/cancel',  [StockTransformationController::class, 'cancel'])->name('transformations.cancel');
    Route::get('transformations/{transformation}/print',    [StockTransformationController::class, 'print'])->name('transformations.print');
    
    // ── TỒN KHO ───────────────────────────────────────────────────────
    Route::prefix('inventory')->name('inventory.')->group(function () {
        Route::get('/',              [InventoryController::class, 'index'])->name('index');
        Route::get('/ledger',        [InventoryController::class, 'ledger'])->name('ledger');
        Route::get('/ledger/export', [InventoryController::class, 'exportLedger'])->name('ledger.export');
        Route::get('/locations',     [InventoryController::class, 'locations'])->name('locations');
    });

});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('transfers/{transfer}/print', [StockTransferController::class, 'printPdf'])->name('transfers.print');
    Route::get('scraps/{scrap}/print',       [ScrapController::class,       'printPdf'])->name('scraps.print');
    Route::get('receipts/{receipt}/print',   [StockReceiptController::class, 'printPdf'])->name('receipts.print');
    Route::get('issues/{issue}/print', [StockIssueController::class, 'printPdf'])->name('issues.print');
});

require __DIR__.'/auth.php';