<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->string('code', 50)->unique();
            $table->string('name', 100);
            $table->tinyInteger('type')->default(1)
                  ->comment('1=Internal, 2=Virtual/Supplier, 3=Virtual/Customer, 4=Virtual/Scrap, 5=Virtual/Quarantine');
            $table->string('barcode', 100)->nullable();
            $table->decimal('capacity_limit', 18, 3)->nullable();
            $table->tinyInteger('status')->default(1)->comment('1=Active, 0=Inactive');

            $table->foreign('parent_id')->references('id')->on('locations')->onDelete('no action');
        });

        DB::statement('CREATE UNIQUE INDEX locations_barcode_unique ON locations (barcode) WHERE barcode IS NOT NULL');

        // Seed các vị trí ảo bắt buộc (theo spec)
        DB::table('locations')->insert([
            // Vị trí gốc thực
            ['parent_id' => null, 'code' => 'WH',         'name' => 'Kho chính',               'type' => 1, 'barcode' => null, 'capacity_limit' => null, 'status' => 1],
            // Vị trí ảo hệ thống
            ['parent_id' => null, 'code' => 'VIRTUAL-SUP', 'name' => 'Nguồn nhập (NCC)',        'type' => 2, 'barcode' => null, 'capacity_limit' => null, 'status' => 1],
            ['parent_id' => null, 'code' => 'VIRTUAL-CUS', 'name' => 'Điểm xuất (Khách hàng)', 'type' => 3, 'barcode' => null, 'capacity_limit' => null, 'status' => 1],
            ['parent_id' => null, 'code' => 'VIRTUAL-SCR', 'name' => 'Khu vực hủy (Scrap)',    'type' => 4, 'barcode' => null, 'capacity_limit' => null, 'status' => 1],
            ['parent_id' => null, 'code' => 'VIRTUAL-QUA', 'name' => 'Khu cách ly (Quarantine)','type' => 5, 'barcode' => null, 'capacity_limit' => null, 'status' => 1],
        ]);

        // Seed một số vị trí kho thực mẫu (con của WH=id 1)
        DB::table('locations')->insert([
            ['parent_id' => 1, 'code' => 'WH-PALLET',  'name' => 'Khu vực Pallet',  'type' => 1, 'barcode' => null, 'capacity_limit' => null, 'status' => 1],
            ['parent_id' => 1, 'code' => 'WH-SHELF',   'name' => 'Khu vực Kệ',      'type' => 1, 'barcode' => null, 'capacity_limit' => null, 'status' => 1],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('locations');
    }
};