<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 200);
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->text('description')->nullable();
            $table->tinyInteger('status')->default(1)->comment('1=Active, 0=Inactive');

            $table->foreign('parent_id')->references('id')->on('categories')->onDelete('no action');
        });

        // Seed dữ liệu mẫu
        DB::table('categories')->insert([
            ['code' => 'MAY',  'name' => 'Máy móc công nghiệp', 'parent_id' => null, 'description' => null, 'status' => 1],
            ['code' => 'LK',   'name' => 'Linh kiện',           'parent_id' => null, 'description' => null, 'status' => 1],
            ['code' => 'NVL',  'name' => 'Nguyên vật liệu',     'parent_id' => null, 'description' => null, 'status' => 1],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};