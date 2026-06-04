<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 200);
            $table->unsignedBigInteger('category_id')->nullable();
            $table->unsignedBigInteger('uom_id');
            $table->unsignedBigInteger('uom_purchase_id')->nullable();
            $table->decimal('weight', 10, 3)->nullable();
            $table->decimal('volume', 10, 3)->nullable();
            $table->string('barcode', 100)->nullable()->unique();
            $table->integer('alert_before_expiry')->nullable()->comment('Cảnh báo trước N ngày hết hạn');
            $table->tinyInteger('tracking_type')->default(1)->comment('1=None, 2=Lot, 3=Serial, 4=LotAndSerial');
            $table->tinyInteger('stock_rotation')->default(1)->comment('1=FIFO, 2=FEFO, 3=Thủ công');
            $table->string('image_path', 500)->nullable();
            $table->text('description')->nullable();
            $table->tinyInteger('status')->default(1)->comment('1=Active, 0=Inactive');
            $table->timestamps();

            $table->foreign('category_id')->references('id')->on('categories')->onDelete('no action');
            $table->foreign('uom_id')->references('id')->on('uoms')->onDelete('no action');
            $table->foreign('uom_purchase_id')->references('id')->on('uoms')->onDelete('no action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
