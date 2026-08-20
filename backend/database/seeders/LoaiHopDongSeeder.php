<?php

namespace Database\Seeders;

use App\Models\LoaiHopDong;
use Illuminate\Database\Seeder;

class LoaiHopDongSeeder extends Seeder
{
    public function run(): void
    {
        $keepCodes = [];

        foreach ($this->catalog() as $item) {
            $keepCodes[] = $item['ma_hop_dong'];

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

        // Ngừng các loại cũ không còn trong danh mục mới (giữ lại vì có thể còn FK).
        LoaiHopDong::query()
            ->whereNotIn('ma_hop_dong', $keepCodes)
            ->update(['trang_thai' => 'ngung_hoat_dong']);

        $this->command?->info('Đã seed '.count($keepCodes).' loại hợp đồng.');
    }

    /**
     * @return list<array{ma_hop_dong: string, ten_hop_dong: string, noi_dung: array, thong_tin_dieu_phoi: array}>
     */
    public function catalog(): array
    {
        $dieuPhoi = $this->defaultThongTinDieuPhoi();

        return [
            [
                'ma_hop_dong' => 'PREWED',
                'ten_hop_dong' => 'Pre-wed',
                'noi_dung' => [
                    'truong' => [
                        $this->field('Tên chú rể', 'tenChuRe', 'input', true),
                        $this->field('Tên cô dâu', 'tenCoDau', 'input', true),
                        $this->field('Ngày chụp', 'ngayChup', 'date', true),
                        $this->field('Địa điểm chụp', 'diaDiemChup', 'textarea', false),
                        $this->field('Ghi chú', 'ghiChu', 'textarea', false),
                    ],
                ],
                'thong_tin_dieu_phoi' => $dieuPhoi,
            ],
            [
                'ma_hop_dong' => 'PSC',
                'ten_hop_dong' => 'Phóng sự cưới',
                'noi_dung' => [
                    'truong' => [
                        $this->field('Tên chú rể', 'tenChuRe', 'input', true),
                        $this->field('Tên cô dâu', 'tenCoDau', 'input', true),
                        $this->field('Ngày cưới', 'ngayCuoi', 'date', true),
                        $this->field('Địa điểm', 'diaDiem', 'textarea', false),
                        $this->field('Ghi chú', 'ghiChu', 'textarea', false),
                    ],
                ],
                'thong_tin_dieu_phoi' => $dieuPhoi,
            ],
            [
                'ma_hop_dong' => 'ANHOI',
                'ten_hop_dong' => 'Ăn hỏi',
                'noi_dung' => [
                    'truong' => [
                        $this->field('Tên chú rể', 'tenChuRe', 'input', true),
                        $this->field('Tên cô dâu', 'tenCoDau', 'input', true),
                        $this->field('Ngày ăn hỏi', 'ngayAnHoi', 'date', true),
                        $this->field('Địa điểm', 'diaDiem', 'textarea', false),
                        $this->field('Ghi chú', 'ghiChu', 'textarea', false),
                    ],
                ],
                'thong_tin_dieu_phoi' => $dieuPhoi,
            ],
            [
                'ma_hop_dong' => 'GIADINH',
                'ten_hop_dong' => 'Gia đình',
                'noi_dung' => [
                    'truong' => [
                        $this->field('Họ tên khách', 'hoTenKhach', 'input', true),
                        $this->field('Số người', 'soNguoi', 'number', false),
                        $this->field('Ngày chụp', 'ngayChup', 'date', true),
                        $this->field('Địa điểm chụp', 'diaDiemChup', 'textarea', false),
                        $this->field('Ghi chú', 'ghiChu', 'textarea', false),
                    ],
                ],
                'thong_tin_dieu_phoi' => $dieuPhoi,
            ],
            [
                'ma_hop_dong' => 'KYYEU',
                'ten_hop_dong' => 'Kỷ yếu',
                'noi_dung' => [
                    'truong' => [
                        $this->field('Tên trường / đơn vị', 'tenDonVi', 'input', true),
                        $this->field('Lớp / khóa', 'lopKhoa', 'input', false),
                        $this->field('Ngày chụp', 'ngayChup', 'date', true),
                        $this->field('Số người', 'soNguoi', 'number', false),
                        $this->field('Ghi chú', 'ghiChu', 'textarea', false),
                    ],
                ],
                'thong_tin_dieu_phoi' => $dieuPhoi,
            ],
            [
                'ma_hop_dong' => 'BEAUTY',
                'ten_hop_dong' => 'Beauty',
                'noi_dung' => [
                    'truong' => [
                        $this->field('Họ tên khách', 'hoTenKhach', 'input', true),
                        $this->field('Ngày chụp', 'ngayChup', 'date', true),
                        $this->field('Số concept', 'soConcept', 'number', false),
                        $this->field('Ghi chú', 'ghiChu', 'textarea', false),
                    ],
                ],
                'thong_tin_dieu_phoi' => $dieuPhoi,
            ],
            [
                'ma_hop_dong' => 'BABYNB',
                'ten_hop_dong' => 'Baby/NB',
                'noi_dung' => [
                    'truong' => [
                        $this->field('Tên bé', 'tenBe', 'input', true),
                        $this->field('Họ tên phụ huynh', 'hoTenPhuHuynh', 'input', true),
                        $this->field('Ngày chụp', 'ngayChup', 'date', true),
                        $this->field('Tuổi / tháng tuổi', 'tuoiBe', 'input', false),
                        $this->field('Ghi chú', 'ghiChu', 'textarea', false),
                    ],
                ],
                'thong_tin_dieu_phoi' => $dieuPhoi,
            ],
            [
                'ma_hop_dong' => 'CHANDUNG',
                'ten_hop_dong' => 'Chân dung',
                'noi_dung' => [
                    'truong' => [
                        $this->field('Họ tên khách', 'hoTenKhach', 'input', true),
                        $this->field('Ngày chụp', 'ngayChup', 'date', true),
                        $this->field('Mục đích chụp', 'mucDichChup', 'input', false),
                        $this->field('Ghi chú', 'ghiChu', 'textarea', false),
                    ],
                ],
                'thong_tin_dieu_phoi' => $dieuPhoi,
            ],
            [
                'ma_hop_dong' => 'COUPLE',
                'ten_hop_dong' => 'Couple',
                'noi_dung' => [
                    'truong' => [
                        $this->field('Tên người 1', 'tenNguoi1', 'input', true),
                        $this->field('Tên người 2', 'tenNguoi2', 'input', true),
                        $this->field('Ngày chụp', 'ngayChup', 'date', true),
                        $this->field('Địa điểm chụp', 'diaDiemChup', 'textarea', false),
                        $this->field('Ghi chú', 'ghiChu', 'textarea', false),
                    ],
                ],
                'thong_tin_dieu_phoi' => $dieuPhoi,
            ],
            [
                'ma_hop_dong' => 'MEBAU',
                'ten_hop_dong' => 'Mẹ bầu',
                'noi_dung' => [
                    'truong' => [
                        $this->field('Họ tên mẹ bầu', 'hoTenMeBau', 'input', true),
                        $this->field('Tuần thai', 'tuanThai', 'number', false),
                        $this->field('Ngày chụp', 'ngayChup', 'date', true),
                        $this->field('Ghi chú', 'ghiChu', 'textarea', false),
                    ],
                ],
                'thong_tin_dieu_phoi' => $dieuPhoi,
            ],
            [
                'ma_hop_dong' => 'SINHNHAT',
                'ten_hop_dong' => 'Sinh nhật',
                'noi_dung' => [
                    'truong' => [
                        $this->field('Họ tên khách', 'hoTenKhach', 'input', true),
                        $this->field('Ngày sinh nhật', 'ngaySinhNhat', 'date', false),
                        $this->field('Ngày chụp', 'ngayChup', 'date', true),
                        $this->field('Chủ đề', 'chuDe', 'input', false),
                        $this->field('Ghi chú', 'ghiChu', 'textarea', false),
                    ],
                ],
                'thong_tin_dieu_phoi' => $dieuPhoi,
            ],
            [
                'ma_hop_dong' => 'SUKIEN',
                'ten_hop_dong' => 'Sự kiện',
                'noi_dung' => [
                    'truong' => [
                        $this->field('Tên sự kiện', 'tenSuKien', 'input', true),
                        $this->field('Người liên hệ', 'nguoiLienHe', 'input', true),
                        $this->field('Ngày sự kiện', 'ngaySuKien', 'date', true),
                        $this->field('Địa điểm', 'diaDiem', 'textarea', false),
                        $this->field('Ghi chú', 'ghiChu', 'textarea', false),
                    ],
                ],
                'thong_tin_dieu_phoi' => $dieuPhoi,
            ],
        ];
    }

    /**
     * Schema mặc định thông tin điều phối cho mỗi loại hợp đồng.
     *
     * @return array<string, array{su_dung: bool, ten_thong_tin: string, loai_du_lieu: string, gia_tri: mixed, gia_tri_toi_thieu?: int, gia_tri_toi_da?: int}>
     */
    public function defaultThongTinDieuPhoi(): array
    {
        return [
            'buoi_chup' => $this->dieuPhoiField('Buổi chụp', 'string', true, null),
            'gio_chup' => $this->dieuPhoiField('Giờ chụp', 'time', true, null),
            'ngay_chup' => $this->dieuPhoiField('Ngày chụp', 'date', true, null),
            'so_diem_chup' => array_merge(
                $this->dieuPhoiField('Số điểm chụp', 'number', true, 1),
                [
                    'gia_tri_toi_thieu' => 1,
                    'gia_tri_toi_da' => 3,
                ]
            ),
            'ngay_tra_demo' => $this->dieuPhoiField('Ngày trả demo', 'date', true, null),
            'ngay_tra_chinh_thuc' => $this->dieuPhoiField('Ngày trả chính thức', 'date', true, null),
            'dia_diem_chup' => $this->dieuPhoiField('Địa điểm chụp', 'string', true, null),
            'tho_chup' => $this->dieuPhoiField('Thợ chụp', 'array', true, []),
            'tho_chup_ngoai' => $this->dieuPhoiField('Thợ chụp ngoài', 'string', true, null),
            'tho_make' => $this->dieuPhoiField('Thợ make', 'array', true, []),
            'tho_make_ngoai' => $this->dieuPhoiField('Thợ make ngoài', 'string', true, null),
            'tho_edit' => $this->dieuPhoiField('Thợ edit', 'array', true, []),
            'tho_edit_ngoai' => $this->dieuPhoiField('Thợ edit ngoài', 'string', true, null),
            'quay_phim' => $this->dieuPhoiField('Quay phim', 'array', true, []),
            'quay_phim_ngoai' => $this->dieuPhoiField('Quay phim ngoài', 'string', true, null),
            'ghi_chu_dieu_phoi' => $this->dieuPhoiField('Ghi chú điều phối', 'textarea', true, null),
            'ghi_chu_trang_phuc_phu_kien' => $this->dieuPhoiField(
                'Yêu cầu khách hàng',
                'textarea',
                true,
                null
            ),
        ];
    }

    /**
     * @return array{su_dung: bool, ten_thong_tin: string, loai_du_lieu: string, gia_tri: mixed}
     */
    private function dieuPhoiField(
        string $tenThongTin,
        string $loaiDuLieu,
        bool $suDung,
        mixed $giaTri
    ): array {
        return [
            'su_dung' => $suDung,
            'ten_thong_tin' => $tenThongTin,
            'loai_du_lieu' => $loaiDuLieu,
            'gia_tri' => $giaTri,
        ];
    }

    /**
     * @return array{ten_truong: string, key: string, kieu: string, bat_buoc: bool}
     */
    private function field(string $ten, string $key, string $kieu, bool $batBuoc): array
    {
        return [
            'ten_truong' => $ten,
            'key' => $key,
            'kieu' => $kieu,
            'bat_buoc' => $batBuoc,
        ];
    }
}
