<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phieu_thu_chi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nguoi_tao_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('nguoi_duyet_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('loai', ['thu', 'chi']);
            $table->foreignId('hang_muc_id')->nullable()->constrained('hang_muc_loai_thu_chi')->nullOnDelete();
            $table->decimal('so_tien', 15, 0)->default(0);
            $table->text('ly_do')->nullable();
            $table->enum('trang_thai', ['cho_duyet', 'da_duyet', 'tu_choi'])->default('cho_duyet');
            $table->timestamp('ngay_cap_nhat_trang_thai')->nullable();
            $table->text('ghi_chu')->nullable();
            $table->timestamps();

            $table->index('loai');
            $table->index('trang_thai');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phieu_thu_chi');
    }
};
