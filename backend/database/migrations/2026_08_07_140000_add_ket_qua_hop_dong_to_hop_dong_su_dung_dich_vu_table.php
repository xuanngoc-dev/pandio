<?php

use App\Models\HopDongSuDungDichVu;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hop_dong_su_dung_dich_vu', function (Blueprint $table) {
            $table->json('ket_qua_hop_dong')->nullable()->after('thong_tin_dieu_phoi');
        });

        $default = json_encode(
            HopDongSuDungDichVu::defaultKetQuaHopDong(),
            JSON_UNESCAPED_UNICODE
        );

        DB::table('hop_dong_su_dung_dich_vu')->update([
            'ket_qua_hop_dong' => $default,
        ]);
    }

    public function down(): void
    {
        Schema::table('hop_dong_su_dung_dich_vu', function (Blueprint $table) {
            $table->dropColumn('ket_qua_hop_dong');
        });
    }
};
