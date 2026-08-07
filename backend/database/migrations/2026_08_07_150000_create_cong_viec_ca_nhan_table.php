<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cong_viec_ca_nhan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nguoi_giao_viec_id')
                ->constrained('users')
                ->restrictOnDelete();
            $table->json('nguoi_phu_trach_viec_ids');
            $table->string('tieu_de');
            $table->text('mo_ta')->nullable();
            $table->text('ghi_chu')->nullable();
            // Khoảng thời gian: { bat_dau: "Y-m-d", ket_thuc: "Y-m-d" }
            $table->json('thoi_gian_thuc_hien')->nullable();
            $table->unsignedTinyInteger('muc_do_uu_tien')->default(1); // 1–5
            $table->string('trang_thai', 32)->default('chua_hoan_thanh'); // chua_hoan_thanh | da_hoan_thanh
            $table->timestamps();

            $table->index('nguoi_giao_viec_id');
            $table->index('muc_do_uu_tien');
            $table->index('trang_thai');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cong_viec_ca_nhan');
    }
};
