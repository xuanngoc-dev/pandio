<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hop_dong_su_dung_dich_vu', function (Blueprint $table) {
            $table->string('ten_khach_hang')->nullable()->after('loai_hop_dong_id');
            $table->string('sdt_khach_hang', 20)->nullable()->after('ten_khach_hang');
            $table->string('dia_chi')->nullable()->after('sdt_khach_hang');
        });
    }

    public function down(): void
    {
        Schema::table('hop_dong_su_dung_dich_vu', function (Blueprint $table) {
            $table->dropColumn(['ten_khach_hang', 'sdt_khach_hang', 'dia_chi']);
        });
    }
};
