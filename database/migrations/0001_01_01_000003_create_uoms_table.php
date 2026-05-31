<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('uoms', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->unique();
            $table->tinyInteger('status')->default(1)->comment('1=Active, 0=Inactive');
        });

        // Seed một số đơn vị tính mặc định
        DB::table('uoms')->insert([
            ['name' => 'Cái',   'status' => 1],
            ['name' => 'Cuộn',  'status' => 1],
            ['name' => 'Kg',    'status' => 1],
            ['name' => 'Hộp',   'status' => 1],
            ['name' => 'Bộ',    'status' => 1],
            ['name' => 'Mét',   'status' => 1],
            ['name' => 'Lít',   'status' => 1],
            ['name' => 'Tấm',   'status' => 1],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('uoms');
    }
};
