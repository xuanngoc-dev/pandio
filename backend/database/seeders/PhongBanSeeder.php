<?php

namespace Database\Seeders;

use App\Models\PhongBan;
use Illuminate\Database\Seeder;

class PhongBanSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            [
                'ma_phong_ban' => 'BGD',
                'ten_phong_ban' => 'Ban Giám đốc',
                'truong_phong' => null,
                'mo_ta' => 'Điều hành và định hướng chiến lược công ty',
            ],
            [
                'ma_phong_ban' => 'KD',
                'ten_phong_ban' => 'Phòng Kinh doanh',
                'truong_phong' => null,
                'mo_ta' => 'Tư vấn, chốt hợp đồng và chăm sóc khách hàng',
            ],
            [
                'ma_phong_ban' => 'MKT',
                'ten_phong_ban' => 'Phòng Marketing',
                'truong_phong' => null,
                'mo_ta' => 'Truyền thông, quảng cáo và xây dựng thương hiệu',
            ],
            [
                'ma_phong_ban' => 'CA',
                'ten_phong_ban' => 'Phòng Chụp ảnh',
                'truong_phong' => null,
                'mo_ta' => 'Chụp ảnh cưới, sự kiện và studio',
            ],
            [
                'ma_phong_ban' => 'QP',
                'ten_phong_ban' => 'Phòng Quay phim',
                'truong_phong' => null,
                'mo_ta' => 'Quay phim cưới, phóng sự và highlight',
            ],
            [
                'ma_phong_ban' => 'MU',
                'ten_phong_ban' => 'Phòng Makeup',
                'truong_phong' => null,
                'mo_ta' => 'Trang điểm cô dâu và sự kiện',
            ],
            [
                'ma_phong_ban' => 'TP',
                'ten_phong_ban' => 'Phòng Trang phục',
                'truong_phong' => null,
                'mo_ta' => 'Quản lý và cho thuê trang phục cưới',
            ],
            [
                'ma_phong_ban' => 'HK',
                'ten_phong_ban' => 'Phòng Hậu kỳ',
                'truong_phong' => null,
                'mo_ta' => 'Chỉnh sửa ảnh, dựng phim và bàn giao sản phẩm',
            ],
            [
                'ma_phong_ban' => 'KT',
                'ten_phong_ban' => 'Phòng Kế toán',
                'truong_phong' => null,
                'mo_ta' => 'Thu chi, công nợ và báo cáo tài chính',
            ],
            [
                'ma_phong_ban' => 'NS',
                'ten_phong_ban' => 'Phòng Nhân sự',
                'truong_phong' => null,
                'mo_ta' => 'Tuyển dụng, chấm công và chế độ nhân sự',
            ],
        ];

        foreach ($items as $item) {
            PhongBan::query()->updateOrCreate(
                ['ma_phong_ban' => $item['ma_phong_ban']],
                [
                    'ten_phong_ban' => $item['ten_phong_ban'],
                    'truong_phong' => $item['truong_phong'],
                    'mo_ta' => $item['mo_ta'],
                    'ghi_chu' => null,
                ]
            );
        }

        $this->command?->info('Đã seed '.count($items).' phòng ban mẫu.');
    }
}
