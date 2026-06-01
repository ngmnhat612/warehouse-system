<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ─── LOT ─────────────────────────────────────────────────────────────
        Schema::create('lots', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->string('lot_number', 100)->comment('Số lô — unique per product');
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->date('manufacture_date')->nullable();
            $table->date('received_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->tinyInteger('status')->default(1)
                  ->comment('1=Active, 2=Quarantine, 3=Expired, 4=Consumed');
            $table->timestamps();

            $table->foreign('product_id')->references('id')->on('products')->onDelete('no action');
            $table->foreign('supplier_id')->references('id')->on('suppliers')->onDelete('no action');

            $table->unique(['product_id', 'lot_number'], 'lots_product_lot_unique');
            $table->index(['product_id', 'status']);
            $table->index('expiry_date');
        });

        // ─── SERIAL ──────────────────────────────────────────────────────────
        Schema::create('serials', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->string('serial_number', 100)->comment('Số serial — unique per product');
            $table->unsignedBigInteger('lot_id')->nullable();       // NULL nếu serial độc lập
            $table->unsignedBigInteger('supplier_id')->nullable();  // NULL nếu lấy từ lot
            $table->date('manufacture_date')->nullable();
            $table->date('received_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->tinyInteger('status')->default(1)
                  ->comment('1=InStock, 2=Quarantine, 3=Defective, 4=Issued, 5=Returned');
            $table->timestamps();

            $table->foreign('product_id')->references('id')->on('products')->onDelete('no action');
            $table->foreign('lot_id')->references('id')->on('lots')->onDelete('no action');
            $table->foreign('supplier_id')->references('id')->on('suppliers')->onDelete('no action');

            $table->unique(['product_id', 'serial_number'], 'serials_product_serial_unique');
            $table->index(['product_id', 'status']);
        });

        // ─── STOCK ───────────────────────────────────────────────────────────
        // Tồn kho hiện tại — mỗi dòng là 1 (product × location × lot × serial)
        Schema::create('stock', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('location_id');
            $table->unsignedBigInteger('lot_id')->nullable();
            $table->unsignedBigInteger('serial_id')->nullable();

            $table->decimal('quantity', 18, 3)->default(0);
            $table->decimal('reserved_qty', 18, 3)->default(0);
            // available_qty = quantity - reserved_qty (computed — SQL Server PERSISTED)
            // Fallback cho DB khác: tính trong Model accessor

            // Thông tin cho hàng thường (lot_id NULL, serial_id NULL)
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->date('manufacture_date')->nullable();
            $table->date('received_date')->nullable();
            $table->date('expiry_date')->nullable();

            $table->tinyInteger('status')->default(1)
                  ->comment('1=Normal, 2=Quarantine, 3=Expired');
            $table->timestamp('updated_at')->nullable();

            $table->foreign('product_id')->references('id')->on('products')->onDelete('no action');
            $table->foreign('location_id')->references('id')->on('locations')->onDelete('no action');
            $table->foreign('lot_id')->references('id')->on('lots')->onDelete('no action');
            $table->foreign('serial_id')->references('id')->on('serials')->onDelete('no action');
            $table->foreign('supplier_id')->references('id')->on('suppliers')->onDelete('no action');

            // Index tra cứu tồn kho — dùng nhiều trong mọi luồng
            $table->index(['product_id', 'location_id']);
            $table->index(['product_id', 'lot_id']);
            $table->index(['product_id', 'serial_id']);
            $table->index('expiry_date');
        });

        // Computed column available_qty — SQL Server only
        \DB::statement('ALTER TABLE stock ADD available_qty AS (quantity - reserved_qty) PERSISTED');

        // ─── STOCK LEDGER ─────────────────────────────────────────────────────
        // Nhật ký kho — KHÔNG BAO GIỜ sửa/xóa
        Schema::create('stock_ledger', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('stock_id');
            $table->unsignedBigInteger('lot_id')->nullable();
            $table->unsignedBigInteger('serial_id')->nullable();
            $table->unsignedBigInteger('location_id');

            $table->string('transaction_type', 20)
                  ->comment('RECEIPT|ISSUE|TRANSFER|SCRAP|ADJUST|TRANSFORM|RETURN');
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('reference_type', 50)->nullable()
                  ->comment('stock_receipt|stock_issue|stock_transfer|scrap|...');
            $table->string('reference_code', 50)->nullable();

            $table->tinyInteger('direction')->comment('1=In, 2=Out');
            $table->decimal('quantity', 18, 3);
            $table->decimal('balance_after', 18, 3);

            $table->unsignedBigInteger('created_by')->nullable();
            $table->string('note', 500)->nullable();
            $table->timestamp('transaction_date')->useCurrent();

            $table->foreign('product_id')->references('id')->on('products')->onDelete('no action');
            $table->foreign('stock_id')->references('id')->on('stock')->onDelete('no action');
            $table->foreign('location_id')->references('id')->on('locations')->onDelete('no action');
            $table->foreign('lot_id')->references('id')->on('lots')->onDelete('no action');
            $table->foreign('serial_id')->references('id')->on('serials')->onDelete('no action');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('no action');

            $table->index(['product_id', 'transaction_date']);
            $table->index(['reference_id', 'reference_type']);
            $table->index('transaction_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_ledger');
        Schema::dropIfExists('stock');
        Schema::dropIfExists('serials');
        Schema::dropIfExists('lots');
    }
};