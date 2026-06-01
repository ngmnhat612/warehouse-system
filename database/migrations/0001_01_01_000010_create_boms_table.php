<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Header BOM
        Schema::create('boms', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique()->comment('Mã BOM, VD: BOM-001');
            $table->string('name', 200)->comment('Tên công thức');
            $table->tinyInteger('type')->default(1)
                  ->comment('1=Tách (Disassemble), 2=Ghép (Assemble)');
            $table->text('note')->nullable();
            $table->tinyInteger('status')->default(1)->comment('1=Active, 0=Inactive');
            $table->timestamps();
        });

        // Chi tiết BOM
        Schema::create('bom_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('bom_id');
            $table->unsignedBigInteger('product_id');
            $table->tinyInteger('line_type')->default(1)
                  ->comment('1=Consume (nguyên liệu đầu vào), 2=Produce (sản phẩm đầu ra)');
            $table->decimal('qty', 18, 3)->default(1);
            $table->unsignedBigInteger('uom_id');
            $table->text('note')->nullable();

            $table->foreign('bom_id')->references('id')->on('boms')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('no action');
            $table->foreign('uom_id')->references('id')->on('uoms')->onDelete('no action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bom_details');
        Schema::dropIfExists('boms');
    }
};