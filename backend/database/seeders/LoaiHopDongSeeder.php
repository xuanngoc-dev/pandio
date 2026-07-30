<?php

namespace Database\Seeders;

use App\Models\LoaiHopDong;
use Illuminate\Database\Seeder;

class LoaiHopDongSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            [
                'ma_hop_dong' => 'SDDV',
                'ten_hop_dong' => 'Hợp đồng sử dụng dịch vụ cưới',
                'trang_thai' => 'hoat_dong',
                'noi_dung' => [
                    'truong' => [
                        [
                            'ten_truong' => 'Tên chú rể',
                            'key' => 'tenChuRe',
                            'kieu' => 'input',
                            'bat_buoc' => true,
                        ],
                        [
                            'ten_truong' => 'Tên cô dâu',
                            'key' => 'tenCoDau',
                            'kieu' => 'input',
                            'bat_buoc' => true,
                        ],
                        [
                            'ten_truong' => 'Số điện thoại liên hệ',
                            'key' => 'soDienThoai',
                            'kieu' => 'phone',
                            'bat_buoc' => true,
                        ],
                        [
                            'ten_truong' => 'Email',
                            'key' => 'email',
                            'kieu' => 'email',
                            'bat_buoc' => false,
                        ],
                        [
                            'ten_truong' => 'Ngày cưới',
                            'key' => 'ngayCuoi',
                            'kieu' => 'date',
                            'bat_buoc' => true,
                        ],
                        [
                            'ten_truong' => 'Địa điểm tổ chức',
                            'key' => 'diaDiemToChuc',
                            'kieu' => 'textarea',
                            'bat_buoc' => true,
                        ],
                        [
                            'ten_truong' => 'Gói dịch vụ',
                            'key' => 'goiDichVu',
                            'kieu' => 'select',
                            'bat_buoc' => true,
                            'options' => [
                                ['label' => 'Gói cơ bản', 'value' => 'co_ban'],
                                ['label' => 'Gói tiêu chuẩn', 'value' => 'tieu_chuan'],
                                ['label' => 'Gói cao cấp', 'value' => 'cao_cap'],
                                ['label' => 'Gói VIP', 'value' => 'vip'],
                            ],
                        ],
                        [
                            'ten_truong' => 'Tổng giá trị hợp đồng',
                            'key' => 'tongGiaTri',
                            'kieu' => 'money',
                            'bat_buoc' => true,
                        ],
                        [
                            'ten_truong' => 'Tiền đặt cọc',
                            'key' => 'tienDatCoc',
                            'kieu' => 'money',
                            'bat_buoc' => true,
                        ],
                        [
                            'ten_truong' => 'Hình thức thanh toán',
                            'key' => 'hinhThucThanhToan',
                            'kieu' => 'radio',
                            'bat_buoc' => true,
                            'options' => [
                                ['label' => 'Tiền mặt', 'value' => 'tien_mat'],
                                ['label' => 'Chuyển khoản', 'value' => 'chuyen_khoan'],
                                ['label' => 'Kết hợp', 'value' => 'ket_hop'],
                            ],
                        ],
                        [
                            'ten_truong' => 'Dịch vụ kèm theo',
                            'key' => 'dichVuKemTheo',
                            'kieu' => 'checkbox_group',
                            'bat_buoc' => false,
                            'options' => [
                                ['label' => 'Makeup', 'value' => 'makeup'],
                                ['label' => 'Thuê trang phục', 'value' => 'trang_phuc'],
                                ['label' => 'Chụp ảnh', 'value' => 'chup_anh'],
                                ['label' => 'Quay phim', 'value' => 'quay_phim'],
                                ['label' => 'Album', 'value' => 'album'],
                            ],
                        ],
                        [
                            'ten_truong' => 'Ghi chú',
                            'key' => 'ghiChu',
                            'kieu' => 'textarea',
                            'bat_buoc' => false,
                        ],
                    ],
                ],
            ],
            [
                'ma_hop_dong' => 'HTTP',
                'ten_hop_dong' => 'Hợp đồng thuê trang phục',
                'trang_thai' => 'hoat_dong',
                'noi_dung' => [
                    'truong' => [
                        [
                            'ten_truong' => 'Họ tên khách hàng',
                            'key' => 'hoTenKhachHang',
                            'kieu' => 'input',
                            'bat_buoc' => true,
                        ],
                        [
                            'ten_truong' => 'Số điện thoại',
                            'key' => 'soDienThoai',
                            'kieu' => 'phone',
                            'bat_buoc' => true,
                        ],
                        [
                            'ten_truong' => 'Loại trang phục',
                            'key' => 'loaiTrangPhuc',
                            'kieu' => 'select',
                            'bat_buoc' => true,
                            'options' => [
                                ['label' => 'Áo dài cô dâu', 'value' => 'ao_dai_co_dau'],
                                ['label' => 'Váy cưới', 'value' => 'vay_cuoi'],
                                ['label' => 'Vest chú rể', 'value' => 'vest_chu_re'],
                                ['label' => 'Áo dài chú rể', 'value' => 'ao_dai_chu_re'],
                                ['label' => 'Trang phục khác', 'value' => 'khac'],
                            ],
                        ],
                        [
                            'ten_truong' => 'Mã sản phẩm',
                            'key' => 'maSanPham',
                            'kieu' => 'input',
                            'bat_buoc' => true,
                        ],
                        [
                            'ten_truong' => 'Size',
                            'key' => 'size',
                            'kieu' => 'select',
                            'bat_buoc' => true,
                            'options' => [
                                ['label' => 'S', 'value' => 'S'],
                                ['label' => 'M', 'value' => 'M'],
                                ['label' => 'L', 'value' => 'L'],
                                ['label' => 'XL', 'value' => 'XL'],
                                ['label' => 'Đo riêng', 'value' => 'do_rieng'],
                            ],
                        ],
                        [
                            'ten_truong' => 'Ngày nhận',
                            'key' => 'ngayNhan',
                            'kieu' => 'date',
                            'bat_buoc' => true,
                        ],
                        [
                            'ten_truong' => 'Ngày trả',
                            'key' => 'ngayTra',
                            'kieu' => 'date',
                            'bat_buoc' => true,
                        ],
                        [
                            'ten_truong' => 'Giá thuê',
                            'key' => 'giaThue',
                            'kieu' => 'money',
                            'bat_buoc' => true,
                        ],
                        [
                            'ten_truong' => 'Tiền thế chân',
                            'key' => 'tienTheChan',
                            'kieu' => 'money',
                            'bat_buoc' => true,
                        ],
                        [
                            'ten_truong' => 'Có chỉnh sửa / may đo',
                            'key' => 'coChinhSua',
                            'kieu' => 'switch',
                            'bat_buoc' => false,
                        ],
                        [
                            'ten_truong' => 'Ảnh trang phục',
                            'key' => 'anhTrangPhuc',
                            'kieu' => 'image',
                            'bat_buoc' => false,
                        ],
                        [
                            'ten_truong' => 'Ghi chú tình trạng',
                            'key' => 'ghiChuTinhTrang',
                            'kieu' => 'textarea',
                            'bat_buoc' => false,
                        ],
                    ],
                ],
            ],
            [
                'ma_hop_dong' => 'HDCA',
                'ten_hop_dong' => 'Hợp đồng chụp ảnh cưới',
                'trang_thai' => 'hoat_dong',
                'noi_dung' => [
                    'truong' => [
                        [
                            'ten_truong' => 'Tên chú rể',
                            'key' => 'tenChuRe',
                            'kieu' => 'input',
                            'bat_buoc' => true,
                        ],
                        [
                            'ten_truong' => 'Tên cô dâu',
                            'key' => 'tenCoDau',
                            'kieu' => 'input',
                            'bat_buoc' => true,
                        ],
                        [
                            'ten_truong' => 'Số điện thoại',
                            'key' => 'soDienThoai',
                            'kieu' => 'phone',
                            'bat_buoc' => true,
                        ],
                        [
                            'ten_truong' => 'Ngày chụp',
                            'key' => 'ngayChup',
                            'kieu' => 'date',
                            'bat_buoc' => true,
                        ],
                        [
                            'ten_truong' => 'Giờ bắt đầu',
                            'key' => 'gioBatDau',
                            'kieu' => 'time',
                            'bat_buoc' => true,
                        ],
                        [
                            'ten_truong' => 'Địa điểm chụp',
                            'key' => 'diaDiemChup',
                            'kieu' => 'textarea',
                            'bat_buoc' => true,
                        ],
                        [
                            'ten_truong' => 'Số concept',
                            'key' => 'soConcept',
                            'kieu' => 'number',
                            'bat_buoc' => true,
                        ],
                        [
                            'ten_truong' => 'Số ảnh bàn giao',
                            'key' => 'soAnhBanGiao',
                            'kieu' => 'number',
                            'bat_buoc' => true,
                        ],
                        [
                            'ten_truong' => 'Bao gồm album',
                            'key' => 'baoGomAlbum',
                            'kieu' => 'switch',
                            'bat_buoc' => false,
                        ],
                        [
                            'ten_truong' => 'Phong cách chụp',
                            'key' => 'phongCachChup',
                            'kieu' => 'checkbox_group',
                            'bat_buoc' => false,
                            'options' => [
                                ['label' => 'Studio', 'value' => 'studio'],
                                ['label' => 'Ngoại cảnh', 'value' => 'ngoai_canh'],
                                ['label' => 'Phóng sự', 'value' => 'phong_su'],
                                ['label' => 'Traditional', 'value' => 'traditional'],
                            ],
                        ],
                        [
                            'ten_truong' => 'Tổng giá trị',
                            'key' => 'tongGiaTri',
                            'kieu' => 'money',
                            'bat_buoc' => true,
                        ],
                        [
                            'ten_truong' => 'Link drive ảnh',
                            'key' => 'linkDriveAnh',
                            'kieu' => 'url',
                            'bat_buoc' => false,
                        ],
                        [
                            'ten_truong' => 'Ghi chú yêu cầu',
                            'key' => 'ghiChuYeuCau',
                            'kieu' => 'textarea',
                            'bat_buoc' => false,
                        ],
                    ],
                ],
            ],
            [
                'ma_hop_dong' => 'HDMU',
                'ten_hop_dong' => 'Hợp đồng makeup & trang điểm',
                'trang_thai' => 'hoat_dong',
                'noi_dung' => [
                    'truong' => [
                        [
                            'ten_truong' => 'Họ tên khách',
                            'key' => 'hoTenKhach',
                            'kieu' => 'input',
                            'bat_buoc' => true,
                        ],
                        [
                            'ten_truong' => 'Số điện thoại',
                            'key' => 'soDienThoai',
                            'kieu' => 'phone',
                            'bat_buoc' => true,
                        ],
                        [
                            'ten_truong' => 'Ngày makeup',
                            'key' => 'ngayMakeup',
                            'kieu' => 'date',
                            'bat_buoc' => true,
                        ],
                        [
                            'ten_truong' => 'Giờ hẹn',
                            'key' => 'gioHen',
                            'kieu' => 'time',
                            'bat_buoc' => true,
                        ],
                        [
                            'ten_truong' => 'Loại makeup',
                            'key' => 'loaiMakeup',
                            'kieu' => 'select',
                            'bat_buoc' => true,
                            'options' => [
                                ['label' => 'Makeup cô dâu', 'value' => 'co_dau'],
                                ['label' => 'Makeup dự tiệc', 'value' => 'du_tiec'],
                                ['label' => 'Makeup chụp ảnh', 'value' => 'chup_anh'],
                                ['label' => 'Makeup thử', 'value' => 'thu'],
                            ],
                        ],
                        [
                            'ten_truong' => 'Số lần makeup',
                            'key' => 'soLanMakeup',
                            'kieu' => 'number',
                            'bat_buoc' => true,
                        ],
                        [
                            'ten_truong' => 'Có làm tóc',
                            'key' => 'coLamToc',
                            'kieu' => 'checkbox',
                            'bat_buoc' => false,
                        ],
                        [
                            'ten_truong' => 'Có phụ dâu',
                            'key' => 'coPhuDau',
                            'kieu' => 'switch',
                            'bat_buoc' => false,
                        ],
                        [
                            'ten_truong' => 'Số phụ dâu',
                            'key' => 'soPhuDau',
                            'kieu' => 'number',
                            'bat_buoc' => false,
                        ],
                        [
                            'ten_truong' => 'Địa điểm làm',
                            'key' => 'diaDiemLam',
                            'kieu' => 'radio',
                            'bat_buoc' => true,
                            'options' => [
                                ['label' => 'Tại studio', 'value' => 'studio'],
                                ['label' => 'Tại nhà khách', 'value' => 'nha_khach'],
                                ['label' => 'Tại nhà hàng', 'value' => 'nha_hang'],
                            ],
                        ],
                        [
                            'ten_truong' => 'Giá dịch vụ',
                            'key' => 'giaDichVu',
                            'kieu' => 'money',
                            'bat_buoc' => true,
                        ],
                        [
                            'ten_truong' => 'Ảnh mẫu makeup',
                            'key' => 'anhMauMakeup',
                            'kieu' => 'image',
                            'bat_buoc' => false,
                        ],
                        [
                            'ten_truong' => 'Ghi chú',
                            'key' => 'ghiChu',
                            'kieu' => 'textarea',
                            'bat_buoc' => false,
                        ],
                    ],
                ],
            ],
            [
                'ma_hop_dong' => 'HDQP',
                'ten_hop_dong' => 'Hợp đồng quay phim cưới',
                'trang_thai' => 'hoat_dong',
                'noi_dung' => [
                    'truong' => [
                        [
                            'ten_truong' => 'Tên chú rể',
                            'key' => 'tenChuRe',
                            'kieu' => 'input',
                            'bat_buoc' => true,
                        ],
                        [
                            'ten_truong' => 'Tên cô dâu',
                            'key' => 'tenCoDau',
                            'kieu' => 'input',
                            'bat_buoc' => true,
                        ],
                        [
                            'ten_truong' => 'Số điện thoại',
                            'key' => 'soDienThoai',
                            'kieu' => 'phone',
                            'bat_buoc' => true,
                        ],
                        [
                            'ten_truong' => 'Ngày quay',
                            'key' => 'ngayQuay',
                            'kieu' => 'date',
                            'bat_buoc' => true,
                        ],
                        [
                            'ten_truong' => 'Thời lượng phim (phút)',
                            'key' => 'thoiLuongPhim',
                            'kieu' => 'number',
                            'bat_buoc' => true,
                        ],
                        [
                            'ten_truong' => 'Gói quay',
                            'key' => 'goiQuay',
                            'kieu' => 'select',
                            'bat_buoc' => true,
                            'options' => [
                                ['label' => 'Highlight', 'value' => 'highlight'],
                                ['label' => 'Phóng sự đầy đủ', 'value' => 'phong_su'],
                                ['label' => 'Cinema', 'value' => 'cinema'],
                                ['label' => 'Combo highlight + phóng sự', 'value' => 'combo'],
                            ],
                        ],
                        [
                            'ten_truong' => 'Thiết bị sử dụng',
                            'key' => 'thietBiSuDung',
                            'kieu' => 'checkbox_group',
                            'bat_buoc' => false,
                            'options' => [
                                ['label' => 'Camera chính', 'value' => 'camera_chinh'],
                                ['label' => 'Camera phụ', 'value' => 'camera_phu'],
                                ['label' => 'Flycam', 'value' => 'flycam'],
                                ['label' => 'Gimbal', 'value' => 'gimbal'],
                                ['label' => 'Micro không dây', 'value' => 'micro'],
                            ],
                        ],
                        [
                            'ten_truong' => 'Có livestream',
                            'key' => 'coLivestream',
                            'kieu' => 'switch',
                            'bat_buoc' => false,
                        ],
                        [
                            'ten_truong' => 'Tổng giá trị',
                            'key' => 'tongGiaTri',
                            'kieu' => 'money',
                            'bat_buoc' => true,
                        ],
                        [
                            'ten_truong' => 'Tiền đặt cọc',
                            'key' => 'tienDatCoc',
                            'kieu' => 'money',
                            'bat_buoc' => true,
                        ],
                        [
                            'ten_truong' => 'Ngày bàn giao dự kiến',
                            'key' => 'ngayBanGiao',
                            'kieu' => 'date',
                            'bat_buoc' => false,
                        ],
                        [
                            'ten_truong' => 'Link bàn giao',
                            'key' => 'linkBanGiao',
                            'kieu' => 'url',
                            'bat_buoc' => false,
                        ],
                        [
                            'ten_truong' => 'Ghi chú',
                            'key' => 'ghiChu',
                            'kieu' => 'textarea',
                            'bat_buoc' => false,
                        ],
                    ],
                ],
            ],
            [
                'ma_hop_dong' => 'HDAL',
                'ten_hop_dong' => 'Hợp đồng in album cưới',
                'trang_thai' => 'hoat_dong',
                'noi_dung' => [
                    'truong' => [
                        [
                            'ten_truong' => 'Họ tên khách hàng',
                            'key' => 'hoTenKhachHang',
                            'kieu' => 'input',
                            'bat_buoc' => true,
                        ],
                        [
                            'ten_truong' => 'Số điện thoại',
                            'key' => 'soDienThoai',
                            'kieu' => 'phone',
                            'bat_buoc' => true,
                        ],
                        [
                            'ten_truong' => 'Email nhận thông báo',
                            'key' => 'email',
                            'kieu' => 'email',
                            'bat_buoc' => false,
                        ],
                        [
                            'ten_truong' => 'Loại album',
                            'key' => 'loaiAlbum',
                            'kieu' => 'select',
                            'bat_buoc' => true,
                            'options' => [
                                ['label' => 'Album photobook', 'value' => 'photobook'],
                                ['label' => 'Album truyền thống', 'value' => 'truyen_thong'],
                                ['label' => 'Album mica', 'value' => 'mica'],
                                ['label' => 'Album da', 'value' => 'da'],
                            ],
                        ],
                        [
                            'ten_truong' => 'Kích thước',
                            'key' => 'kichThuoc',
                            'kieu' => 'radio',
                            'bat_buoc' => true,
                            'options' => [
                                ['label' => '20x20', 'value' => '20x20'],
                                ['label' => '25x25', 'value' => '25x25'],
                                ['label' => '30x30', 'value' => '30x30'],
                                ['label' => '30x45', 'value' => '30x45'],
                            ],
                        ],
                        [
                            'ten_truong' => 'Số trang',
                            'key' => 'soTrang',
                            'kieu' => 'number',
                            'bat_buoc' => true,
                        ],
                        [
                            'ten_truong' => 'Số cuốn',
                            'key' => 'soCuon',
                            'kieu' => 'number',
                            'bat_buoc' => true,
                        ],
                        [
                            'ten_truong' => 'Tháng giao hàng',
                            'key' => 'thangGiaoHang',
                            'kieu' => 'month',
                            'bat_buoc' => false,
                        ],
                        [
                            'ten_truong' => 'Giá album',
                            'key' => 'giaAlbum',
                            'kieu' => 'money',
                            'bat_buoc' => true,
                        ],
                        [
                            'ten_truong' => 'Phần trăm giảm giá',
                            'key' => 'phanTramGiamGia',
                            'kieu' => 'percent',
                            'bat_buoc' => false,
                        ],
                        [
                            'ten_truong' => 'File thiết kế',
                            'key' => 'fileThietKe',
                            'kieu' => 'file',
                            'bat_buoc' => false,
                        ],
                        [
                            'ten_truong' => 'Ghi chú thiết kế',
                            'key' => 'ghiChuThietKe',
                            'kieu' => 'textarea',
                            'bat_buoc' => false,
                        ],
                    ],
                ],
            ],
        ];

        foreach ($items as $item) {
            LoaiHopDong::query()->updateOrCreate(
                ['ma_hop_dong' => $item['ma_hop_dong']],
                [
                    'ten_hop_dong' => $item['ten_hop_dong'],
                    'noi_dung' => $item['noi_dung'],
                    'trang_thai' => $item['trang_thai'],
                ]
            );
        }

        $this->command?->info('Đã seed '.count($items).' loại hợp đồng mẫu.');
    }
}
