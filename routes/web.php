<?php

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

});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';