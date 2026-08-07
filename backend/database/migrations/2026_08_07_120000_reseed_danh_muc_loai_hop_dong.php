<?php

use App\Models\LoaiHopDong;
use Database\Seeders\LoaiHopDongSeeder;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $seeder = new LoaiHopDongSeeder;
        $catalog = $seeder->catalog();
        $keepCodes = array_column($catalog, 'ma_hop_dong');

        // Đồng bộ bản ghi cũ "Pre-wedding" → PREWED nếu còn.
        LoaiHopDong::query()
            ->where('ma_hop_dong', 'Pre-wedding')
            ->update([
                'ma_hop_dong' => 'PREWED',
                'ten_hop_dong' => 'Pre-wed',
            ]);

        foreach ($catalog as $item) {
            LoaiHopDong::query()->updateOrCreate(
                ['ma_hop_dong' => $item['ma_hop_dong']],
                [
                    'ten_hop_dong' => $item['ten_hop_dong'],
                    'noi_dung' => $item['noi_dung'],
                    'thong_tin_dieu_phoi' => $item['thong_tin_dieu_phoi'] ?? null,
                    'trang_thai' => 'hoat_dong',
                ]
            );
        }

        LoaiHopDong::query()
            ->whereNotIn('ma_hop_dong', $keepCodes)
            ->update(['trang_thai' => 'ngung_hoat_dong']);
    }

    public function down(): void
    {
        // Không rollback dữ liệu seed.
    }
};
