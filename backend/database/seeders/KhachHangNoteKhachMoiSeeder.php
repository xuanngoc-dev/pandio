<?php

namespace Database\Seeders;

use App\Models\KhachHangNoteKhachMoi;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class KhachHangNoteKhachMoiSeeder extends Seeder
{
    public function run(): void
    {
        $userIds = User::query()->orderBy('id')->pluck('id')->all();
        if ($userIds === []) {
            $this->command?->warn('Không có user nào — bỏ qua seed note khách mới.');

            return;
        }

        $tenKhach = [
            'Nguyễn Thị Mai', 'Trần Văn Hùng', 'Lê Thị Hoa', 'Phạm Minh Tuấn',
            'Hoàng Thị Lan', 'Vũ Đức Anh', 'Đặng Thanh Thảo', 'Bùi Quang Huy',
            'Ngô Thị Hằng', 'Đỗ Văn Nam', 'Lý Thị Ngọc', 'Trịnh Minh Khoa',
            'Phan Thị Yến', 'Võ Quốc Bảo', 'Huỳnh Thị Kim', 'Đinh Văn Phúc',
            'Cao Thị Dung', 'Lương Minh Nhật', 'Tô Thị Quỳnh', 'Hà Văn Sơn',
        ];

        $ghiChu = [
            null,
            null,
            'Khách hỏi concept cưới ngoài trời',
            'Muốn xem mẫu trang phục trước',
            'Hẹn lại vì bận công việc',
            'Đã gửi báo giá qua Zalo',
            'Quan tâm gói chụp studio',
            'Có người giới thiệu',
            'Cần tư vấn ngân sách',
            'Đặt lịch cuối tuần',
        ];

        $nguonKhach = ['tiktok', 'facebook', 'google', 'gioi_thieu', 'walk_in', 'khac'];
        $trangThai = ['cho_hen', 'da_den', 'khong_den', 'da_ky_hd', 'da_huy'];
        $hinhThucDatCoc = ['tien_mat', 'chuyen_khoan', 'khong_coc', 'khac', null];

        $start = Carbon::today()->subDays(45);
        $now = now();
        $rows = [];

        for ($i = 0; $i < 60; $i++) {
            $ngayHen = $start->copy()->addDays((int) floor($i * 0.9))->addHours(random_int(8, 18));
            $trangThaiVal = $trangThai[array_rand($trangThai)];

            $ngayDen = null;
            if (in_array($trangThaiVal, ['da_den', 'da_ky_hd'], true)) {
                $ngayDen = $ngayHen->copy()->addDays(random_int(0, 2))->toDateString();
            } elseif ($trangThaiVal === 'khong_den' && random_int(0, 1) === 1) {
                $ngayDen = null;
            }

            $saleCount = random_int(1, min(3, count($userIds)));
            $saleIds = collect($userIds)->shuffle()->take($saleCount)->values()->all();

            $rows[] = [
                'ten_khach' => $tenKhach[$i % count($tenKhach)].($i >= count($tenKhach) ? ' '.($i + 1) : ''),
                'sdt' => '09'.str_pad((string) random_int(10000000, 99999999), 8, '0', STR_PAD_LEFT),
                'ngay_hen_lich' => $ngayHen->toDateString(),
                'phu_trach_sale' => json_encode($saleIds),
                'ghi_chu' => $ghiChu[array_rand($ghiChu)],
                'nguon_khach' => $nguonKhach[array_rand($nguonKhach)],
                'ngay_den_thuc_te' => $ngayDen,
                'trang_thai' => $trangThaiVal,
                'tra_cuu_hd' => random_int(0, 2) === 0
                    ? 'HD-'.($ngayHen->format('Ymd')).'-'.str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT)
                    : null,
                'hinh_thuc_dat_coc' => $hinhThucDatCoc[array_rand($hinhThucDatCoc)],
                'nguoi_tao' => $userIds[array_rand($userIds)],
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        KhachHangNoteKhachMoi::query()->insert($rows);
    }
}
