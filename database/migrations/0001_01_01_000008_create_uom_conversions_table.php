<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('uom_conversions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('from_uom_id');
            $table->unsignedBigInteger('to_uom_id');
            $table->decimal('factor', 18, 6)->comment('1 from_uom = factor * to_uom');
            $table->tinyInteger('status')->default(1)->comment('1=Active, 0=Inactive');

            $table->foreign('from_uom_id')->references('id')->on('uoms')->onDelete('no action');
            $table->foreign('to_uom_id')->references('id')->on('uoms')->onDelete('no action');

            // Mỗi cặp (from → to) chỉ tồn tại 1 lần
            $table->unique(['from_uom_id', 'to_uom_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('uom_conversions');
    }
};