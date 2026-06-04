<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ─── STOCK RECEIPT ────────────────────────────────────────────────────
        Schema::create('stock_receipts', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->tinyInteger('receipt_type')->default(1)
                  ->comment('1=NCC, 2=Trả hàng từ SX/bảo trì, 3=Khác');
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->string('reference_no', 100)->nullable()->comment('Số PO / chứng từ ngoài');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('confirmed_by')->nullable();
            $table->tinyInteger('status')->default(1)
                  ->comment('1=Draft, 2=Pending, 3=Approved, 4=Completed, 5=Cancelled');
            $table->date('receipt_date')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->foreign('supplier_id')->references('id')->on('suppliers')->onDelete('no action');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('no action');
            $table->foreign('confirmed_by')->references('id')->on('users')->onDelete('no action');
            $table->index(['status', 'created_at']);
        });

        Schema::create('stock_receipt_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('stock_receipt_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('lot_id')->nullable();
            $table->unsignedBigInteger('serial_id')->nullable();
            $table->unsignedBigInteger('location_id')->nullable();
            $table->unsignedBigInteger('uom_id');
            $table->decimal('expected_qty', 18, 3)->default(0);
            $table->decimal('actual_qty', 18, 3)->nullable();
            $table->decimal('rejected_qty', 18, 3)->default(0);
            $table->string('reject_reason', 500)->nullable();
            $table->tinyInteger('qc_status')->default(0)
                  ->comment('0=Không cần, 1=Pass, 2=Fail, 3=Pending');
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->date('manufacture_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->text('note')->nullable();

            $table->foreign('stock_receipt_id')->references('id')->on('stock_receipts')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('no action');
            $table->foreign('lot_id')->references('id')->on('lots')->onDelete('no action');
            $table->foreign('serial_id')->references('id')->on('serials')->onDelete('no action');
            $table->foreign('location_id')->references('id')->on('locations')->onDelete('no action');
            $table->foreign('uom_id')->references('id')->on('uoms')->onDelete('no action');
            $table->foreign('supplier_id')->references('id')->on('suppliers')->onDelete('no action');
        });

        // ─── STOCK ISSUE ──────────────────────────────────────────────────────
        Schema::create('stock_issues', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->tinyInteger('issue_type')->default(1)
                  ->comment('1=Sản xuất, 2=Bảo trì, 3=Mượn, 4=Khác');
            $table->unsignedBigInteger('requester_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('confirmed_by')->nullable();
            $table->tinyInteger('status')->default(1)
                  ->comment('1=Draft, 2=Pending, 3=Approved, 4=Completed, 5=Cancelled');
            $table->date('issue_date')->nullable();
            $table->date('expected_return_date')->nullable()->comment('Chỉ dùng khi Mượn');
            $table->string('reference_no', 100)->nullable()->comment('Số lệnh SX / công việc');
            $table->text('note')->nullable();
            $table->timestamps();

            $table->foreign('requester_id')->references('id')->on('users')->onDelete('no action');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('no action');
            $table->foreign('confirmed_by')->references('id')->on('users')->onDelete('no action');
            $table->index(['status', 'created_at']);
        });

        Schema::create('stock_issue_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('stock_issue_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('lot_id')->nullable();
            $table->unsignedBigInteger('serial_id')->nullable();
            $table->unsignedBigInteger('location_id');
            $table->unsignedBigInteger('uom_id');
            $table->decimal('quantity', 18, 3)->nullable()->comment('NULL nếu serial (luôn = 1)');
            $table->text('note')->nullable();

            $table->foreign('stock_issue_id')->references('id')->on('stock_issues')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('no action');
            $table->foreign('lot_id')->references('id')->on('lots')->onDelete('no action');
            $table->foreign('serial_id')->references('id')->on('serials')->onDelete('no action');
            $table->foreign('location_id')->references('id')->on('locations')->onDelete('no action');
            $table->foreign('uom_id')->references('id')->on('uoms')->onDelete('no action');
        });

        // ─── STOCK TRANSFER ───────────────────────────────────────────────────
        Schema::create('stock_transfers', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->tinyInteger('transfer_type')->default(1)
                ->comment('1=Sắp xếp kho, 2=Từ Quarantine, 3=Khác');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('confirmed_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();  // đặt đây, bỏ ->after()
            $table->tinyInteger('status')->default(1);
            $table->date('transfer_date')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('users')->onDelete('no action');
            $table->foreign('confirmed_by')->references('id')->on('users')->onDelete('no action');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('no action');
        });

        Schema::create('stock_transfer_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('stock_transfer_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('lot_id')->nullable();
            $table->unsignedBigInteger('serial_id')->nullable();
            $table->unsignedBigInteger('from_location_id');
            $table->unsignedBigInteger('to_location_id');
            $table->unsignedBigInteger('uom_id');
            $table->decimal('quantity', 18, 3);
            $table->text('note')->nullable();

            $table->foreign('stock_transfer_id')->references('id')->on('stock_transfers')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('no action');
            $table->foreign('lot_id')->references('id')->on('lots')->onDelete('no action');
            $table->foreign('serial_id')->references('id')->on('serials')->onDelete('no action');
            $table->foreign('from_location_id')->references('id')->on('locations')->onDelete('no action');
            $table->foreign('to_location_id')->references('id')->on('locations')->onDelete('no action');
            $table->foreign('uom_id')->references('id')->on('uoms')->onDelete('no action');
        });

        // ─── SCRAP ────────────────────────────────────────────────────────────
        Schema::create('scraps', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->tinyInteger('status')->default(1);
            $table->date('scrap_date')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('users')->onDelete('no action');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('no action');
        });

        Schema::create('scrap_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('scrap_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('lot_id')->nullable();
            $table->unsignedBigInteger('serial_id')->nullable();
            $table->unsignedBigInteger('location_id');
            $table->unsignedBigInteger('uom_id');
            $table->decimal('quantity', 18, 3);
            $table->string('reason', 500)->nullable();

            $table->foreign('scrap_id')->references('id')->on('scraps')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('no action');
            $table->foreign('lot_id')->references('id')->on('lots')->onDelete('no action');
            $table->foreign('serial_id')->references('id')->on('serials')->onDelete('no action');
            $table->foreign('location_id')->references('id')->on('locations')->onDelete('no action');
            $table->foreign('uom_id')->references('id')->on('uoms')->onDelete('no action');
        });

        // ─── STOCK TRANSFORMATION (Tách/Ghép) ────────────────────────────────
        Schema::create('stock_transformations', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->tinyInteger('type')->default(1)->comment('1=Tách, 2=Ghép');
            $table->unsignedBigInteger('bom_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('confirmed_by')->nullable();
            $table->tinyInteger('status')->default(1);
            $table->date('transformation_date')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->foreign('bom_id')->references('id')->on('boms')->onDelete('no action');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('no action');
            $table->foreign('confirmed_by')->references('id')->on('users')->onDelete('no action');
        });

        Schema::create('stock_transformation_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('stock_transformation_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('lot_id')->nullable();
            $table->unsignedBigInteger('serial_id')->nullable();
            $table->unsignedBigInteger('location_id');
            $table->unsignedBigInteger('uom_id');
            $table->tinyInteger('direction')->comment('1=Consume, 2=Produce');
            $table->decimal('quantity', 18, 3);
            $table->date('expiry_date')->nullable();

            $table->foreign('stock_transformation_id')->references('id')->on('stock_transformations')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('no action');
            $table->foreign('lot_id')->references('id')->on('lots')->onDelete('no action');
            $table->foreign('serial_id')->references('id')->on('serials')->onDelete('no action');
            $table->foreign('location_id')->references('id')->on('locations')->onDelete('no action');
            $table->foreign('uom_id')->references('id')->on('uoms')->onDelete('no action');
        });

        // ─── INVENTORY CHECK ──────────────────────────────────────────────────
        Schema::create('inventory_checks', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->tinyInteger('check_type')->default(1)
                  ->comment('1=Toàn kho, 2=Theo khu vực, 3=Theo mặt hàng');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('assigned_to')->nullable();
            $table->tinyInteger('status')->default(1)
                  ->comment('1=Draft, 2=InProgress, 3=Done, 4=Cancelled');
            $table->date('check_date')->nullable();
            $table->text('note')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('users')->onDelete('no action');
            $table->foreign('assigned_to')->references('id')->on('users')->onDelete('no action');
        });

        Schema::create('inventory_check_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('inventory_check_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('lot_id')->nullable();
            $table->unsignedBigInteger('serial_id')->nullable();
            $table->unsignedBigInteger('location_id');
            $table->unsignedBigInteger('uom_id');
            $table->decimal('system_qty', 18, 3)->default(0)
                  ->comment('Snapshot tồn tại thời điểm bắt đầu kiểm kê');
            $table->decimal('actual_qty', 18, 3)->nullable()
                  ->comment('Số lượng đếm thực tế');
            // diff_qty = actual_qty - system_qty (computed)
            $table->unsignedBigInteger('counted_by')->nullable();
            $table->timestamp('counted_at')->nullable();

            $table->foreign('inventory_check_id')->references('id')->on('inventory_checks')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('no action');
            $table->foreign('lot_id')->references('id')->on('lots')->onDelete('no action');
            $table->foreign('serial_id')->references('id')->on('serials')->onDelete('no action');
            $table->foreign('location_id')->references('id')->on('locations')->onDelete('no action');
            $table->foreign('uom_id')->references('id')->on('uoms')->onDelete('no action');
            $table->foreign('counted_by')->references('id')->on('users')->onDelete('no action');
        });

        \DB::statement('ALTER TABLE inventory_check_lines ADD diff_qty AS (actual_qty - system_qty) PERSISTED');

        // ─── INVENTORY FREEZE ─────────────────────────────────────────────────
        Schema::create('inventory_freezes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('check_id');
            $table->tinyInteger('check_type')->default(1);
            $table->unsignedBigInteger('frozen_by')->nullable();
            $table->timestamp('frozen_at')->useCurrent();
            $table->timestamp('unfrozen_at')->nullable()->comment('NULL = đang đóng băng');
            $table->string('reason', 200)->nullable();

            $table->foreign('check_id')->references('id')->on('inventory_checks')->onDelete('cascade');
            $table->foreign('frozen_by')->references('id')->on('users')->onDelete('no action');
        });

        Schema::create('inventory_freeze_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('freeze_id');
            $table->tinyInteger('freeze_scope')
                  ->comment('1=Toàn kho, 2=location_id, 3=product_id');
            $table->unsignedBigInteger('location_id')->nullable();
            $table->unsignedBigInteger('product_id')->nullable();

            $table->foreign('freeze_id')->references('id')->on('inventory_freezes')->onDelete('cascade');
            $table->foreign('location_id')->references('id')->on('locations')->onDelete('no action');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('no action');
        });

        // ─── STOCK ADJUSTMENT ─────────────────────────────────────────────────
        Schema::create('stock_adjustments', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->unsignedBigInteger('inventory_check_id')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('confirmed_by')->nullable();
            $table->tinyInteger('status')->default(1)
                  ->comment('1=Draft, 2=Pending, 3=Approved, 4=Applied, 5=Rejected');
            $table->date('adjustment_date')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->foreign('inventory_check_id')->references('id')->on('inventory_checks')->onDelete('no action');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('no action');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('no action');
            $table->foreign('confirmed_by')->references('id')->on('users')->onDelete('no action');
        });

        Schema::create('stock_adjustment_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('stock_adjustment_id');
            $table->unsignedBigInteger('inventory_check_line_id')->nullable();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('lot_id')->nullable();
            $table->unsignedBigInteger('serial_id')->nullable();
            $table->unsignedBigInteger('uom_id');
            $table->unsignedBigInteger('location_id');
            $table->decimal('system_qty', 18, 3)->default(0);
            $table->decimal('actual_qty', 18, 3)->default(0);
            // diff_qty computed

            $table->foreign('stock_adjustment_id')->references('id')->on('stock_adjustments')->onDelete('cascade');
            $table->foreign('inventory_check_line_id')->references('id')->on('inventory_check_lines')->onDelete('no action');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('no action');
            $table->foreign('lot_id')->references('id')->on('lots')->onDelete('no action');
            $table->foreign('serial_id')->references('id')->on('serials')->onDelete('no action');
            $table->foreign('location_id')->references('id')->on('locations')->onDelete('no action');
            $table->foreign('uom_id')->references('id')->on('uoms')->onDelete('no action');
        });

        \DB::statement('ALTER TABLE stock_adjustment_details ADD diff_qty AS (actual_qty - system_qty) PERSISTED');
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_adjustment_details');
        Schema::dropIfExists('stock_adjustments');
        Schema::dropIfExists('inventory_freeze_details');
        Schema::dropIfExists('inventory_freezes');
        Schema::dropIfExists('inventory_check_lines');
        Schema::dropIfExists('inventory_checks');
        Schema::dropIfExists('stock_transformation_details');
        Schema::dropIfExists('stock_transformations');
        Schema::dropIfExists('scrap_details');
        Schema::dropIfExists('scraps');
        Schema::dropIfExists('stock_transfer_details');
        Schema::dropIfExists('stock_transfers');
        Schema::dropIfExists('stock_issue_details');
        Schema::dropIfExists('stock_issues');
        Schema::dropIfExists('stock_receipt_details');
        Schema::dropIfExists('stock_receipts');
    }
};