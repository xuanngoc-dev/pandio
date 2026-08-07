<?php

use App\Models\LoaiHopDong;
use Database\Seeders\LoaiHopDongSeeder;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $seeder = new LoaiHopDongSeeder;
        $dieuPhoi = $seeder->defaultThongTinDieuPhoi();
        $keepCodes = array_column($seeder->catalog(), 'ma_hop_dong');

        LoaiHopDong::query()
            ->whereIn('ma_hop_dong', $keepCodes)
            ->update(['thong_tin_dieu_phoi' => $dieuPhoi]);
    }

    public function down(): void
    {
        // Không rollback dữ liệu seed.
    }
};
