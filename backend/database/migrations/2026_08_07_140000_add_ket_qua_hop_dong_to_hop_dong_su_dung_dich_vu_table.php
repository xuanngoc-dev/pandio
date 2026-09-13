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
        if (! Schema::hasTable('hop_dong_su_dung_dich_vu')) {
            return;
        }

        if (! Schema::hasColumn('hop_dong_su_dung_dich_vu', 'ket_qua_hop_dong')) {
            Schema::table('hop_dong_su_dung_dich_vu', function (Blueprint $table) {
                $column = $table->json('ket_qua_hop_dong')->nullable();
                if (Schema::hasColumn('hop_dong_su_dung_dich_vu', 'thong_tin_dieu_phoi')) {
                    $column->after('thong_tin_dieu_phoi');
                }
            });
        }

        $default = json_encode(
            HopDongSuDungDichVu::defaultKetQuaHopDong(),
            JSON_UNESCAPED_UNICODE
        );

        DB::table('hop_dong_su_dung_dich_vu')
            ->whereNull('ket_qua_hop_dong')
            ->update([
                'ket_qua_hop_dong' => $default,
            ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('hop_dong_su_dung_dich_vu')
            || ! Schema::hasColumn('hop_dong_su_dung_dich_vu', 'ket_qua_hop_dong')) {
            return;
        }

        Schema::table('hop_dong_su_dung_dich_vu', function (Blueprint $table) {
            $table->dropColumn('ket_qua_hop_dong');
        });
    }
};
