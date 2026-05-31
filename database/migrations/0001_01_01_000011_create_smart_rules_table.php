<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ─── PUTAWAY RULE ────────────────────────────────────────────────────
        // Quy tắc gán vị trí tự động khi nhập kho
        // Ưu tiên: product_id > category_id (product cụ thể được xét trước)
        Schema::create('putaway_rules', function (Blueprint $table) {
            $table->id();

            // Áp dụng theo hàng hóa cụ thể — NULL nếu theo nhóm
            $table->unsignedBigInteger('product_id')->nullable();
            // Áp dụng theo nhóm hàng hóa — NULL nếu theo product
            $table->unsignedBigInteger('category_id')->nullable();

            // Vị trí đích được gán tự động
            $table->unsignedBigInteger('location_dest_id');

            // Ưu tiên: số nhỏ = ưu tiên cao hơn
            $table->integer('priority')->default(10)->comment('Số nhỏ = ưu tiên cao hơn');

            $table->text('note')->nullable();
            $table->tinyInteger('status')->default(1)->comment('1=Active, 0=Inactive');
            $table->timestamps();

            // Foreign keys
            $table->foreign('product_id')
                  ->references('id')->on('products')
                  ->onDelete('cascade');

            $table->foreign('category_id')
                  ->references('id')->on('categories')
                  ->onDelete('cascade');

            $table->foreign('location_dest_id')
                  ->references('id')->on('locations')
                  ->onDelete('no action');

            // Một quy tắc phải có product_id HOẶC category_id (kiểm tra ở tầng application)
            // Index cho tra cứu nhanh khi nhập kho
            $table->index(['product_id', 'status']);
            $table->index(['category_id', 'status']);
        });

        // ─── REORDER RULE ────────────────────────────────────────────────────
        // Quy tắc cảnh báo khi tồn kho xuống dưới ngưỡng
        Schema::create('reorder_rules', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('location_id');

            // Ngưỡng tồn tối thiểu — khi tồn < min_qty thì cảnh báo
            $table->decimal('min_qty', 18, 3)->default(0)
                  ->comment('Ngưỡng tồn tối thiểu, cảnh báo khi tồn < min_qty');

            // Ngưỡng tồn tối đa — số lượng cần đặt thêm để đạt max_qty
            $table->decimal('max_qty', 18, 3)->default(0)
                  ->comment('Ngưỡng tồn tối đa mong muốn');

            // Email cảnh báo (tuỳ chọn, có thể để trống)
            $table->string('alert_email', 200)->nullable()
                  ->comment('Email nhận cảnh báo, để trống nếu không dùng');

            $table->text('note')->nullable();
            $table->tinyInteger('status')->default(1)->comment('1=Active, 0=Inactive');
            $table->timestamps();

            // Foreign keys
            $table->foreign('product_id')
                  ->references('id')->on('products')
                  ->onDelete('cascade');

            $table->foreign('location_id')
                  ->references('id')->on('locations')
                  ->onDelete('cascade');

            // Mỗi (product + location) chỉ có 1 reorder rule
            $table->unique(['product_id', 'location_id'], 'reorder_rules_product_location_unique');

            // Index cho query kiểm tra cảnh báo hàng ngày
            $table->index(['status', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reorder_rules');
        Schema::dropIfExists('putaway_rules');
    }
};