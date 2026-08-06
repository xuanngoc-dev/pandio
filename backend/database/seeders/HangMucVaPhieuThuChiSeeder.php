<?php

namespace Database\Seeders;

use App\Models\HangMucLoaiThuChi;
use App\Models\PhieuThuChi;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class HangMucVaPhieuThuChiSeeder extends Seeder
{
    public function run(): void
    {
        $userIds = User::query()->orderBy('id')->pluck('id')->all();
        if ($userIds === []) {
            $this->command?->warn('Không có user — bỏ qua seed thu chi.');

            return;
        }

        $now = now();

        $hangMucData = [
            ['ten_hang_muc' => 'Thu hợp đồng cưới', 'ghi_chu' => 'Tiền khách thanh toán hợp đồng'],
            ['ten_hang_muc' => 'Thu cho thuê trang phục', 'ghi_chu' => 'Doanh thu thuê trang phục'],
            ['ten_hang_muc' => 'Thu dịch vụ chụp ảnh', 'ghi_chu' => null],
            ['ten_hang_muc' => 'Thu đặt cọc khách', 'ghi_chu' => 'Tiền cọc trước khi ký HĐ'],
            ['ten_hang_muc' => 'Chi quảng cáo', 'ghi_chu' => 'TikTok / FB / Google Ads'],
            ['ten_hang_muc' => 'Chi lương nhân viên', 'ghi_chu' => 'Lương tháng'],
            ['ten_hang_muc' => 'Chi mua trang phục', 'ghi_chu' => 'Nhập hàng trang phục'],
            ['ten_hang_muc' => 'Chi thuê mặt bằng', 'ghi_chu' => 'Tiền thuê studio / cửa hàng'],
            ['ten_hang_muc' => 'Chi vận hành', 'ghi_chu' => 'Điện, nước, văn phòng phẩm'],
            ['ten_hang_muc' => 'Chi khác', 'ghi_chu' => 'Các khoản chi phát sinh'],
        ];

        $hangMucIds = [];
        foreach ($hangMucData as $item) {
            $hangMuc = HangMucLoaiThuChi::query()->create([
                'ten_hang_muc' => $item['ten_hang_muc'],
                'ghi_chu' => $item['ghi_chu'],
                'trang_thai' => 'hoat_dong',
            ]);
            $hangMucIds[] = $hangMuc->id;
        }

        $lyDoThu = [
            'Thanh toán đợt 1 hợp đồng',
            'Khách đặt cọc giữ lịch',
            'Thanh toán đủ hợp đồng',
            'Thu tiền thuê váy cưới',
            'Thu phí makeup + chụp',
        ];

        $lyDoChi = [
            'Chạy ads TikTok tuần này',
            'Thanh toán lương tháng',
            'Mua trang phục mới',
            'Đóng tiền thuê mặt bằng',
            'Mua vật tư studio',
            'Chi phí đi lại làm việc ngoài',
        ];

        $trangThaiList = ['cho_duyet', 'da_duyet', 'tu_choi'];
        $rows = [];
        $start = Carbon::today()->subDays(40);

        for ($i = 0; $i < 50; $i++) {
            $isThu = $i % 5 !== 0; // ~80% chi? Actually mix - alternate more evenly
            $loai = ($i % 3 === 0) ? 'thu' : 'chi';
            $trangThai = $trangThaiList[array_rand($trangThaiList)];
            $ngayTao = $start->copy()->addDays($i)->setTime(random_int(8, 18), random_int(0, 59));

            $nguoiTao = $userIds[array_rand($userIds)];
            $nguoiDuyet = null;
            $ngayCapNhat = $ngayTao->copy();

            if ($trangThai === 'da_duyet' || $trangThai === 'tu_choi') {
                $nguoiDuyet = $userIds[array_rand($userIds)];
                $ngayCapNhat = $ngayTao->copy()->addHours(random_int(1, 48));
            }

            $hangMucId = $hangMucIds[array_rand($hangMucIds)];
            $soTien = $loai === 'thu'
                ? random_int(1_000_000, 50_000_000)
                : random_int(200_000, 20_000_000);

            // Làm tròn nghìn
            $soTien = (int) (round($soTien / 1000) * 1000);

            $rows[] = [
                'nguoi_tao_id' => $nguoiTao,
                'nguoi_duyet_id' => $nguoiDuyet,
                'loai' => $loai,
                'hang_muc_id' => $hangMucId,
                'so_tien' => $soTien,
                'ly_do' => $loai === 'thu'
                    ? $lyDoThu[array_rand($lyDoThu)]
                    : $lyDoChi[array_rand($lyDoChi)],
                'trang_thai' => $trangThai,
                'ngay_cap_nhat_trang_thai' => $ngayCapNhat,
                'ghi_chu' => random_int(0, 2) === 0 ? 'Dữ liệu mẫu seed' : null,
                'created_at' => $ngayTao,
                'updated_at' => $now,
            ];
        }

        PhieuThuChi::query()->insert($rows);
    }
}
