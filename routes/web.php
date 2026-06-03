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
        Route::get('/',        [ReportController::class, 'index'])->name('index');
        Route::get('/export/excel', [ReportController::class, 'exportExcel'])->name('export.excel');
        Route::get('/export/pdf',   [ReportController::class, 'exportPdf'])->name('export.pdf');
    });

    // ── NHẬT KÝ HỆ THỐNG ──────────────────────────────────────────────
    Route::prefix('activity-log')->name('activity-log.')->group(function () {
        Route::get('/',        [ActivityLogController::class, 'index'])->name('index');
        Route::get('/export',  [ActivityLogController::class, 'export'])->name('export');
    });

    Route::get('receipts/suggest-putaway',    [StockReceiptController::class, 'suggestPutaway'])->name('receipts.suggest-putaway');
    Route::resource('receipts', StockReceiptController::class);
    Route::post('receipts/{receipt}/submit',  [StockReceiptController::class, 'submit'])->name('receipts.submit');
    Route::post('receipts/{receipt}/approve', [StockReceiptController::class, 'approve'])->name('receipts.approve');
    Route::post('receipts/{receipt}/confirm', [StockReceiptController::class, 'confirm'])->name('receipts.confirm');
    Route::post('receipts/{receipt}/cancel',  [StockReceiptController::class, 'cancel'])->name('receipts.cancel');

    Route::resource('issues', StockIssueController::class);
    Route::post('issues/{issue}/submit',  [StockIssueController::class, 'submit'])->name('issues.submit');
    Route::post('issues/{issue}/approve', [StockIssueController::class, 'approve'])->name('issues.approve');
    Route::post('issues/{issue}/confirm', [StockIssueController::class, 'confirm'])->name('issues.confirm');
    Route::post('issues/{issue}/cancel',  [StockIssueController::class, 'cancel'])->name('issues.cancel');

    Route::resource('transfers', StockTransferController::class);
    Route::post('transfers/{transfer}/confirm', [StockTransferController::class, 'confirm'])->name('transfers.confirm');
    Route::post('transfers/{transfer}/cancel',  [StockTransferController::class, 'cancel'])->name('transfers.cancel');

    Route::resource('scraps', ScrapController::class);
    Route::post('scraps/{scrap}/submit',  [ScrapController::class, 'submit'])->name('scraps.submit');
    Route::post('scraps/{scrap}/approve', [ScrapController::class, 'approve'])->name('scraps.approve');
    Route::post('scraps/{scrap}/cancel',  [ScrapController::class, 'cancel'])->name('scraps.cancel');

    Route::resource('stocktakes', InventoryCheckController::class);

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
});

require __DIR__.'/auth.php';
