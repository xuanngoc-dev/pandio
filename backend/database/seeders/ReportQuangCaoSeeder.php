<?php

namespace Database\Seeders;

use App\Models\ReportQuangCao;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class ReportQuangCaoSeeder extends Seeder
{
    public function run(): void
    {
        $notes = [
            null,
            null,
            null,
            'Chạy campaign TikTok mới',
            'Tăng ngân sách FB cuối tuần',
            'Google Ads test A/B',
            'Ngày nghỉ — chi phí thấp',
            'Boost inbox TikTok',
            'Retargeting FB',
            'Promo tháng này',
        ];

        $start = Carbon::today()->subDays(59);
        $rows = [];

        for ($i = 0; $i < 60; $i++) {
            $ngay = $start->copy()->addDays($i);

            $cpqcTiktok = random_int(800_000, 4_500_000);
            $cpqcFb = random_int(500_000, 3_500_000);
            $cpqcGoogle = random_int(300_000, 2_500_000);

            $inboxTiktok = random_int(15, 120);
            $inboxFb = random_int(10, 90);

            $khTiktok = random_int(2, max(2, (int) floor($inboxTiktok * 0.35)));
            $khFb = random_int(1, max(1, (int) floor($inboxFb * 0.35)));
            $khGoogle = random_int(1, 25);

            $lichHen = random_int(1, max(1, $khTiktok + $khFb + $khGoogle));
            $khachDen = random_int(0, $lichHen);

            $rows[] = [
                'ngay' => $ngay->toDateString(),
                'cpqc_tiktok' => $cpqcTiktok,
                'cpqc_fb' => $cpqcFb,
                'cpqc_google' => $cpqcGoogle,
                'inbox_tiktok' => $inboxTiktok,
                'cpi_tiktok' => (int) round($cpqcTiktok / max(1, $inboxTiktok)),
                'inbox_fb' => $inboxFb,
                'cpi_fb' => (int) round($cpqcFb / max(1, $inboxFb)),
                'kh_tiktok' => $khTiktok,
                'kh_fb' => $khFb,
                'kh_google' => $khGoogle,
                'tcpl_tiktok' => (int) round($cpqcTiktok / max(1, $khTiktok)),
                'cpl_fb' => (int) round($cpqcFb / max(1, $khFb)),
                'cpl_google' => (int) round($cpqcGoogle / max(1, $khGoogle)),
                'lich_hen' => $lichHen,
                'khach_den_tu_hen' => $khachDen,
                'ghi_chu' => $notes[array_rand($notes)],
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        ReportQuangCao::query()->insert($rows);
    }
}
