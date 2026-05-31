<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->unique()
                  ->comment('Liên kết tài khoản đăng nhập, null = chưa có tài khoản');
            $table->string('code', 20)->unique()->comment('Mã nhân viên, VD: NV001');
            $table->string('full_name', 100);
            $table->string('phone', 20)->nullable();
            $table->string('email', 100)->nullable();
            $table->tinyInteger('status')->default(1)->comment('1=Active, 0=Inactive');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};