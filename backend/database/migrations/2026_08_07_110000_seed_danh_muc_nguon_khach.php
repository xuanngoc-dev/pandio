<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $rows = [];

        foreach ($this->catalog() as $item) {
            $exists = DB::table('danh_muc_nguon_khach')
                ->where('ten_nguon_khach', $item['ten_nguon_khach'])
                ->exists();

            if ($exists) {
                continue;
            }

            $rows[] = [
                'ten_nguon_khach' => $item['ten_nguon_khach'],
                'ghi_chu' => $item['ghi_chu'],
                'trang_thai' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if ($rows !== []) {
            DB::table('danh_muc_nguon_khach')->insert($rows);
        }
    }

    public function down(): void
    {
        $names = array_column($this->catalog(), 'ten_nguon_khach');

        DB::table('danh_muc_nguon_khach')
            ->whereIn('ten_nguon_khach', $names)
            ->delete();
    }

    /**
     * @return list<array{ten_nguon_khach: string, ghi_chu: string|null}>
     */
    private function catalog(): array
    {
        return [
            ['ten_nguon_khach' => 'Facebook Ads', 'ghi_chu' => 'Quảng cáo Facebook / Meta'],
            ['ten_nguon_khach' => 'Facebook Page', 'ghi_chu' => 'Inbox / tương tác fanpage'],
            ['ten_nguon_khach' => 'TikTok', 'ghi_chu' => 'Organic hoặc ads TikTok'],
            ['ten_nguon_khach' => 'Instagram', 'ghi_chu' => 'DM / tương tác Instagram'],
            ['ten_nguon_khach' => 'Zalo', 'ghi_chu' => 'Zalo OA hoặc Zalo cá nhân'],
            ['ten_nguon_khach' => 'Google Ads', 'ghi_chu' => 'Quảng cáo tìm kiếm Google'],
            ['ten_nguon_khach' => 'Google Maps', 'ghi_chu' => 'Tìm kiếm / đánh giá Maps'],
            ['ten_nguon_khach' => 'Website', 'ghi_chu' => 'Form / chat trên website'],
            ['ten_nguon_khach' => 'YouTube', 'ghi_chu' => 'Video / comment YouTube'],
            ['ten_nguon_khach' => 'Hotline', 'ghi_chu' => 'Gọi điện trực tiếp'],
            ['ten_nguon_khach' => 'Giới thiệu khách cũ', 'ghi_chu' => 'Khách cũ giới thiệu'],
            ['ten_nguon_khach' => 'Giới thiệu CTV', 'ghi_chu' => 'Cộng tác viên / đối tác'],
            ['ten_nguon_khach' => 'Walk-in', 'ghi_chu' => 'Khách đến studio trực tiếp'],
            ['ten_nguon_khach' => 'Hội chợ / Event', 'ghi_chu' => 'Sự kiện, wedding fair'],
            ['ten_nguon_khach' => 'Khác', 'ghi_chu' => 'Nguồn khác chưa phân loại'],
        ];
    }
};
