<?php

use App\Models\DichVuDanhSachDichNhomDichVu;
use App\Models\DichVuDanhSachDichVuLe;
use App\Models\DichVuLoaiDichVu;
use App\Models\LoaiHopDong;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const SEED_PREFIX_DV = 'SEED_DV_';

    private const SEED_PREFIX_CB = 'SEED_CB_';

    public function up(): void
    {
        $loaiHopDongs = LoaiHopDong::query()
            ->orderBy('id')
            ->get(['id', 'ma_hop_dong', 'ten_hop_dong']);

        if ($loaiHopDongs->isEmpty()) {
            return;
        }

        $loaiDichVuMap = $this->ensureLoaiDichVu();

        DB::transaction(function () use ($loaiHopDongs, $loaiDichVuMap) {
            foreach ($loaiHopDongs as $loaiHd) {
                $catalog = $this->catalogFor($loaiHd->ma_hop_dong);
                $loaiDichVuId = $loaiDichVuMap[$catalog['loai_dich_vu']] ?? $loaiDichVuMap['Khác'];

                $dichVuLeIds = [];
                foreach ($catalog['dich_vu'] as $index => $item) {
                    $stt = str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT);
                    $maDichVu = self::SEED_PREFIX_DV.$loaiHd->ma_hop_dong.'_'.$stt;
                    $giaGoc = (int) $item['gia_goc'];
                    $giaKm = isset($item['gia_khuyen_mai'])
                        ? (int) $item['gia_khuyen_mai']
                        : (int) round($giaGoc * 0.9);

                    $dichVu = DichVuDanhSachDichVuLe::query()->updateOrCreate(
                        ['ma_dich_vu' => $maDichVu],
                        [
                            'ten_dich_vu' => $item['ten'],
                            'loai_dich_vu_id' => $loaiDichVuId,
                            'loai_hop_dong_ids' => [(int) $loaiHd->id],
                            'gia_goc' => $giaGoc,
                            'gia_khuyen_mai' => $giaKm,
                            'mo_ta' => $item['mo_ta'] ?? null,
                            'trang_thai' => 'dang_su_dung',
                            'ghi_chu' => 'Dữ liệu mẫu migrate '.$loaiHd->ma_hop_dong,
                        ]
                    );

                    $dichVuLeIds[] = (int) $dichVu->id;
                }

                foreach ($catalog['combo'] as $index => $item) {
                    $stt = str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT);
                    $maNhom = self::SEED_PREFIX_CB.$loaiHd->ma_hop_dong.'_'.$stt;

                    $pickedIndexes = $item['dich_vu_indexes'] ?? range(0, min(4, count($dichVuLeIds) - 1));
                    $pickedIds = [];
                    foreach ($pickedIndexes as $dvIndex) {
                        if (isset($dichVuLeIds[$dvIndex])) {
                            $pickedIds[] = $dichVuLeIds[$dvIndex];
                        }
                    }

                    $giaGoc = (int) ($item['gia_goc'] ?? $this->sumGiaGoc($pickedIds));
                    $giaKm = isset($item['gia_khuyen_mai'])
                        ? (int) $item['gia_khuyen_mai']
                        : (int) round($giaGoc * 0.88);

                    DichVuDanhSachDichNhomDichVu::query()->updateOrCreate(
                        ['ma_nhom' => $maNhom],
                        [
                            'ten_nhom' => $item['ten'],
                            'gia_goc' => $giaGoc,
                            'gia_khuyen_mai' => $giaKm,
                            'loai_hop_dong_id' => (int) $loaiHd->id,
                            'so_diem_chup' => (int) ($item['so_diem_chup'] ?? 0),
                            'so_anh_chinh_sua' => (int) ($item['so_anh_chinh_sua'] ?? 0),
                            'dich_vu_le_ids' => $pickedIds,
                            'trang_thai' => 'dang_su_dung',
                            'ghi_chu' => 'Combo mẫu migrate '.$loaiHd->ma_hop_dong,
                        ]
                    );
                }
            }
        });
    }

    public function down(): void
    {
        DichVuDanhSachDichNhomDichVu::query()
            ->where('ma_nhom', 'like', self::SEED_PREFIX_CB.'%')
            ->delete();

        DichVuDanhSachDichVuLe::query()
            ->where('ma_dich_vu', 'like', self::SEED_PREFIX_DV.'%')
            ->delete();
    }

    /**
     * @return array<string, int>
     */
    private function ensureLoaiDichVu(): array
    {
        $names = [
            'Chụp ảnh',
            'Quay phim',
            'Makeup',
            'Trang phục',
            'Album',
            'Dịch vụ ngày cưới',
            'Khác',
        ];

        $map = [];
        foreach ($names as $name) {
            $item = DichVuLoaiDichVu::query()->firstOrCreate(
                ['ten_dich_vu' => $name],
                [
                    'mo_ta' => 'Loại dịch vụ mẫu: '.$name,
                    'trang_thai' => 'dang_hoat_dong',
                ]
            );
            $map[$name] = (int) $item->id;
        }

        return $map;
    }

    /**
     * @param  list<int>  $ids
     */
    private function sumGiaGoc(array $ids): int
    {
        if ($ids === []) {
            return 0;
        }

        return (int) DichVuDanhSachDichVuLe::query()
            ->whereIn('id', $ids)
            ->sum('gia_goc');
    }

    /**
     * @return array{
     *   loai_dich_vu: string,
     *   dich_vu: list<array{ten: string, gia_goc: int, gia_khuyen_mai?: int, mo_ta?: string}>,
     *   combo: list<array{ten: string, dich_vu_indexes?: list<int>, gia_goc?: int, gia_khuyen_mai?: int, so_diem_chup?: int, so_anh_chinh_sua?: int}>
     * }
     */
    private function catalogFor(string $maHopDong): array
    {
        return match ($maHopDong) {
            'HTTP' => $this->catalogThueTrangPhuc(),
            'HDCA' => $this->catalogChupAnh(),
            'HDMU' => $this->catalogMakeup(),
            'HDQP' => $this->catalogQuayPhim(),
            'HDAL' => $this->catalogAlbum(),
            default => $this->catalogSddv(),
        };
    }

    /**
     * @return array{loai_dich_vu: string, dich_vu: list<array<string, mixed>>, combo: list<array<string, mixed>>}
     */
    private function catalogSddv(): array
    {
        return [
            'loai_dich_vu' => 'Dịch vụ ngày cưới',
            'dich_vu' => [
                ['ten' => 'Trang trí backdrop sân khấu', 'gia_goc' => 3500000],
                ['ten' => 'Trang trí bàn gallery', 'gia_goc' => 1800000],
                ['ten' => 'Cổng hoa chào khách', 'gia_goc' => 2500000],
                ['ten' => 'Hoa cầm tay cô dâu', 'gia_goc' => 900000],
                ['ten' => 'Hoa cài áo chú rể', 'gia_goc' => 250000],
                ['ten' => 'MC dẫn chương trình', 'gia_goc' => 3000000],
                ['ten' => 'Ban nhạc acoustic', 'gia_goc' => 4500000],
                ['ten' => 'DJ đám cưới', 'gia_goc' => 2800000],
                ['ten' => 'Ánh sáng sân khấu', 'gia_goc' => 3200000],
                ['ten' => 'Âm thanh sự kiện', 'gia_goc' => 2700000],
                ['ten' => 'Màn hình LED', 'gia_goc' => 4000000],
                ['ten' => 'Pháo giấy / confetti', 'gia_goc' => 800000],
                ['ten' => 'Bóng bay trang trí', 'gia_goc' => 1200000],
                ['ten' => 'Thiệp mời in ấn (100 cái)', 'gia_goc' => 1500000],
                ['ten' => 'Sổ ký tên khách', 'gia_goc' => 450000],
                ['ten' => 'Xe hoa đưa dâu', 'gia_goc' => 2200000],
                ['ten' => 'Đội bê quả', 'gia_goc' => 1600000],
                ['ten' => 'Tháp champagne', 'gia_goc' => 1100000],
                ['ten' => 'Bánh cưới 3 tầng', 'gia_goc' => 3500000],
                ['ten' => 'Team hỗ trợ lễ đường', 'gia_goc' => 2000000],
            ],
            'combo' => [
                ['ten' => 'Combo lễ đường cơ bản', 'dich_vu_indexes' => [0, 2, 3, 4], 'so_diem_chup' => 0, 'so_anh_chinh_sua' => 0],
                ['ten' => 'Combo sân khấu tiêu chuẩn', 'dich_vu_indexes' => [0, 1, 8, 9], 'so_diem_chup' => 0, 'so_anh_chinh_sua' => 0],
                ['ten' => 'Combo âm thanh ánh sáng', 'dich_vu_indexes' => [8, 9, 10], 'so_diem_chup' => 0, 'so_anh_chinh_sua' => 0],
                ['ten' => 'Combo MC + DJ', 'dich_vu_indexes' => [5, 7], 'so_diem_chup' => 0, 'so_anh_chinh_sua' => 0],
                ['ten' => 'Combo ban nhạc live', 'dich_vu_indexes' => [5, 6, 8], 'so_diem_chup' => 0, 'so_anh_chinh_sua' => 0],
                ['ten' => 'Combo trang trí hoa', 'dich_vu_indexes' => [2, 3, 4, 12], 'so_diem_chup' => 0, 'so_anh_chinh_sua' => 0],
                ['ten' => 'Combo đưa dâu', 'dich_vu_indexes' => [15, 16], 'so_diem_chup' => 0, 'so_anh_chinh_sua' => 0],
                ['ten' => 'Combo tiệc tối tiết kiệm', 'dich_vu_indexes' => [0, 5, 11, 18], 'so_diem_chup' => 0, 'so_anh_chinh_sua' => 0],
                ['ten' => 'Combo tiệc tối cao cấp', 'dich_vu_indexes' => [0, 1, 5, 6, 8, 10, 17, 18], 'so_diem_chup' => 0, 'so_anh_chinh_sua' => 0],
                ['ten' => 'Combo trọn gói ngày cưới', 'dich_vu_indexes' => [0, 2, 3, 5, 7, 8, 9, 15, 18, 19], 'so_diem_chup' => 0, 'so_anh_chinh_sua' => 0],
            ],
        ];
    }

    /**
     * @return array{loai_dich_vu: string, dich_vu: list<array<string, mixed>>, combo: list<array<string, mixed>>}
     */
    private function catalogThueTrangPhuc(): array
    {
        return [
            'loai_dich_vu' => 'Trang phục',
            'dich_vu' => [
                ['ten' => 'Váy cưới công chúa', 'gia_goc' => 4500000],
                ['ten' => 'Váy cưới đuôi dài', 'gia_goc' => 5200000],
                ['ten' => 'Váy cưới chữ A', 'gia_goc' => 3800000],
                ['ten' => 'Váy cưới tối giản', 'gia_goc' => 3200000],
                ['ten' => 'Áo dài cô dâu đỏ', 'gia_goc' => 2800000],
                ['ten' => 'Áo dài cô dâu trắng', 'gia_goc' => 2600000],
                ['ten' => 'Vest chú rể basic', 'gia_goc' => 1800000],
                ['ten' => 'Vest chú rể cao cấp', 'gia_goc' => 2500000],
                ['ten' => 'Áo dài chú rể', 'gia_goc' => 1600000],
                ['ten' => 'Phụ kiện voan cô dâu', 'gia_goc' => 450000],
                ['ten' => 'Găng tay / hand flower', 'gia_goc' => 350000],
                ['ten' => 'Giày cô dâu', 'gia_goc' => 800000],
                ['ten' => 'Giày chú rể', 'gia_goc' => 700000],
                ['ten' => 'Váy phụ dâu', 'gia_goc' => 1500000],
                ['ten' => 'Vest phụ rể', 'gia_goc' => 1200000],
                ['ten' => 'Trang phục chụp pre-wedding', 'gia_goc' => 3000000],
                ['ten' => 'Trang phục concept cổ trang', 'gia_goc' => 3500000],
                ['ten' => 'Trang phục concept Hàn', 'gia_goc' => 3300000],
                ['ten' => 'Thay đồ tại studio', 'gia_goc' => 500000],
                ['ten' => 'Ship trang phục tận nơi', 'gia_goc' => 400000],
            ],
            'combo' => [
                ['ten' => 'Combo cô dâu cơ bản', 'dich_vu_indexes' => [0, 9, 10], 'so_diem_chup' => 0, 'so_anh_chinh_sua' => 0],
                ['ten' => 'Combo cô dâu cao cấp', 'dich_vu_indexes' => [1, 4, 9, 11], 'so_diem_chup' => 0, 'so_anh_chinh_sua' => 0],
                ['ten' => 'Combo chú rể cơ bản', 'dich_vu_indexes' => [6, 12], 'so_diem_chup' => 0, 'so_anh_chinh_sua' => 0],
                ['ten' => 'Combo chú rể cao cấp', 'dich_vu_indexes' => [7, 8, 12], 'so_diem_chup' => 0, 'so_anh_chinh_sua' => 0],
                ['ten' => 'Combo cặp đôi ngày cưới', 'dich_vu_indexes' => [0, 6, 9, 11, 12], 'so_diem_chup' => 0, 'so_anh_chinh_sua' => 0],
                ['ten' => 'Combo áo dài cặp', 'dich_vu_indexes' => [4, 8], 'so_diem_chup' => 0, 'so_anh_chinh_sua' => 0],
                ['ten' => 'Combo pre-wedding 2 set', 'dich_vu_indexes' => [15, 16], 'so_diem_chup' => 0, 'so_anh_chinh_sua' => 0],
                ['ten' => 'Combo concept Hàn + cổ trang', 'dich_vu_indexes' => [16, 17, 18], 'so_diem_chup' => 0, 'so_anh_chinh_sua' => 0],
                ['ten' => 'Combo phụ dâu - phụ rể', 'dich_vu_indexes' => [13, 14], 'so_diem_chup' => 0, 'so_anh_chinh_sua' => 0],
                ['ten' => 'Combo trọn bộ trang phục', 'dich_vu_indexes' => [1, 5, 7, 9, 11, 12, 19], 'so_diem_chup' => 0, 'so_anh_chinh_sua' => 0],
            ],
        ];
    }

    /**
     * @return array{loai_dich_vu: string, dich_vu: list<array<string, mixed>>, combo: list<array<string, mixed>>}
     */
    private function catalogChupAnh(): array
    {
        return [
            'loai_dich_vu' => 'Chụp ảnh',
            'dich_vu' => [
                ['ten' => 'Chụp ảnh studio 1 concept', 'gia_goc' => 4500000],
                ['ten' => 'Chụp ảnh studio 2 concept', 'gia_goc' => 6500000],
                ['ten' => 'Chụp ảnh ngoại cảnh công viên', 'gia_goc' => 3800000],
                ['ten' => 'Chụp ảnh ngoại cảnh biển', 'gia_goc' => 5500000],
                ['ten' => 'Chụp ảnh ngoại cảnh Đà Lạt', 'gia_goc' => 9000000],
                ['ten' => 'Chụp ảnh Phú Quốc', 'gia_goc' => 12000000],
                ['ten' => 'Chụp ảnh nhà thờ', 'gia_goc' => 3200000],
                ['ten' => 'Chụp phóng sự ngày cưới', 'gia_goc' => 4800000],
                ['ten' => 'Chụp ảnh gia đình', 'gia_goc' => 2500000],
                ['ten' => 'Makeup chụp ảnh cô dâu', 'gia_goc' => 1800000],
                ['ten' => 'Makeup chú rể', 'gia_goc' => 600000],
                ['ten' => 'Thuê stylist chụp ảnh', 'gia_goc' => 1500000],
                ['ten' => 'Flycam hỗ trợ chụp', 'gia_goc' => 2200000],
                ['ten' => 'Retouch ảnh nâng cao / tấm', 'gia_goc' => 150000],
                ['ten' => 'In ảnh để bàn (20 tấm)', 'gia_goc' => 800000],
                ['ten' => 'File gốc RAW', 'gia_goc' => 1200000],
                ['ten' => 'Ảnh phóng lớn 40x60', 'gia_goc' => 900000],
                ['ten' => 'Ảnh canvas treo tường', 'gia_goc' => 1500000],
                ['ten' => 'Team 2 photographer', 'gia_goc' => 3000000],
                ['ten' => 'Chụp ảnh sau cưới (after wedding)', 'gia_goc' => 4200000],
            ],
            'combo' => [
                ['ten' => 'Combo studio cơ bản', 'dich_vu_indexes' => [0, 9, 10], 'so_diem_chup' => 3, 'so_anh_chinh_sua' => 30],
                ['ten' => 'Combo studio 2 concept', 'dich_vu_indexes' => [1, 9, 11], 'so_diem_chup' => 5, 'so_anh_chinh_sua' => 50],
                ['ten' => 'Combo ngoại cảnh công viên', 'dich_vu_indexes' => [2, 9], 'so_diem_chup' => 4, 'so_anh_chinh_sua' => 40],
                ['ten' => 'Combo biển lãng mạn', 'dich_vu_indexes' => [3, 9, 12], 'so_diem_chup' => 5, 'so_anh_chinh_sua' => 50],
                ['ten' => 'Combo Đà Lạt', 'dich_vu_indexes' => [4, 9, 11, 12], 'so_diem_chup' => 8, 'so_anh_chinh_sua' => 80],
                ['ten' => 'Combo Phú Quốc premium', 'dich_vu_indexes' => [5, 9, 12, 18], 'so_diem_chup' => 10, 'so_anh_chinh_sua' => 100],
                ['ten' => 'Combo phóng sự cưới', 'dich_vu_indexes' => [7, 18], 'so_diem_chup' => 6, 'so_anh_chinh_sua' => 120],
                ['ten' => 'Combo in ấn lưu niệm', 'dich_vu_indexes' => [14, 16, 17], 'so_diem_chup' => 0, 'so_anh_chinh_sua' => 20],
                ['ten' => 'Combo gia đình + cặp đôi', 'dich_vu_indexes' => [0, 8, 9], 'so_diem_chup' => 4, 'so_anh_chinh_sua' => 45],
                ['ten' => 'Combo trọn gói pre-wedding', 'dich_vu_indexes' => [1, 3, 9, 11, 15, 18], 'so_diem_chup' => 8, 'so_anh_chinh_sua' => 80],
            ],
        ];
    }

    /**
     * @return array{loai_dich_vu: string, dich_vu: list<array<string, mixed>>, combo: list<array<string, mixed>>}
     */
    private function catalogMakeup(): array
    {
        return [
            'loai_dich_vu' => 'Makeup',
            'dich_vu' => [
                ['ten' => 'Makeup cô dâu lễ đường', 'gia_goc' => 2500000],
                ['ten' => 'Makeup cô dâu tiệc tối', 'gia_goc' => 2200000],
                ['ten' => 'Makeup cô dâu ăn hỏi', 'gia_goc' => 1800000],
                ['ten' => 'Makeup cô dâu nhà thờ', 'gia_goc' => 2000000],
                ['ten' => 'Thay makeup giữa buổi', 'gia_goc' => 900000],
                ['ten' => 'Làm tóc cô dâu updo', 'gia_goc' => 1200000],
                ['ten' => 'Làm tóc cô dâu xõa sóng', 'gia_goc' => 1000000],
                ['ten' => 'Gắn phụ kiện tóc', 'gia_goc' => 400000],
                ['ten' => 'Nail cô dâu', 'gia_goc' => 550000],
                ['ten' => 'Makeup chú rể', 'gia_goc' => 500000],
                ['ten' => 'Makeup mẹ cô dâu', 'gia_goc' => 800000],
                ['ten' => 'Makeup mẹ chú rể', 'gia_goc' => 800000],
                ['ten' => 'Makeup phụ dâu (1 người)', 'gia_goc' => 700000],
                ['ten' => 'Trial makeup trước ngày cưới', 'gia_goc' => 1000000],
                ['ten' => 'Makeup chụp pre-wedding', 'gia_goc' => 1500000],
                ['ten' => 'Makeup concept Hàn', 'gia_goc' => 1700000],
                ['ten' => 'Makeup concept cổ trang', 'gia_goc' => 1900000],
                ['ten' => 'Artist đứng sát giờ', 'gia_goc' => 1500000],
                ['ten' => 'Team makeup 2 người', 'gia_goc' => 2800000],
                ['ten' => 'Ship makeup tận nơi', 'gia_goc' => 400000],
            ],
            'combo' => [
                ['ten' => 'Combo cô dâu ngày cưới', 'dich_vu_indexes' => [0, 5, 7], 'so_diem_chup' => 0, 'so_anh_chinh_sua' => 0],
                ['ten' => 'Combo lễ đường + tiệc tối', 'dich_vu_indexes' => [0, 1, 4, 5], 'so_diem_chup' => 0, 'so_anh_chinh_sua' => 0],
                ['ten' => 'Combo ăn hỏi', 'dich_vu_indexes' => [2, 6, 8], 'so_diem_chup' => 0, 'so_anh_chinh_sua' => 0],
                ['ten' => 'Combo nhà thờ', 'dich_vu_indexes' => [3, 5, 7], 'so_diem_chup' => 0, 'so_anh_chinh_sua' => 0],
                ['ten' => 'Combo cặp đôi', 'dich_vu_indexes' => [0, 9], 'so_diem_chup' => 0, 'so_anh_chinh_sua' => 0],
                ['ten' => 'Combo gia đình', 'dich_vu_indexes' => [0, 9, 10, 11], 'so_diem_chup' => 0, 'so_anh_chinh_sua' => 0],
                ['ten' => 'Combo pre-wedding', 'dich_vu_indexes' => [13, 14, 15], 'so_diem_chup' => 0, 'so_anh_chinh_sua' => 0],
                ['ten' => 'Combo concept đặc biệt', 'dich_vu_indexes' => [15, 16], 'so_diem_chup' => 0, 'so_anh_chinh_sua' => 0],
                ['ten' => 'Combo phụ dâu x3', 'dich_vu_indexes' => [12], 'gia_goc' => 1900000, 'so_diem_chup' => 0, 'so_anh_chinh_sua' => 0],
                ['ten' => 'Combo trọn gói makeup ngày cưới', 'dich_vu_indexes' => [0, 1, 4, 5, 7, 8, 9, 17], 'so_diem_chup' => 0, 'so_anh_chinh_sua' => 0],
            ],
        ];
    }

    /**
     * @return array{loai_dich_vu: string, dich_vu: list<array<string, mixed>>, combo: list<array<string, mixed>>}
     */
    private function catalogQuayPhim(): array
    {
        return [
            'loai_dich_vu' => 'Quay phim',
            'dich_vu' => [
                ['ten' => 'Quay phóng sự ngày cưới', 'gia_goc' => 5500000],
                ['ten' => 'Quay highlight 3-5 phút', 'gia_goc' => 2800000],
                ['ten' => 'Quay teaser pre-wedding', 'gia_goc' => 3200000],
                ['ten' => 'Quay phim studio', 'gia_goc' => 4000000],
                ['ten' => 'Quay phim ngoại cảnh', 'gia_goc' => 4800000],
                ['ten' => 'Flycam ngày cưới', 'gia_goc' => 2500000],
                ['ten' => 'Quay phim ăn hỏi', 'gia_goc' => 3000000],
                ['ten' => 'Quay phim nhà thờ', 'gia_goc' => 2800000],
                ['ten' => 'Quay phim tiệc tối', 'gia_goc' => 3500000],
                ['ten' => 'Livestream đám cưới', 'gia_goc' => 4500000],
                ['ten' => 'Dựng phim cinematic', 'gia_goc' => 3800000],
                ['ten' => 'Dựng phim phóng sự đầy đủ', 'gia_goc' => 4200000],
                ['ten' => 'Color grading chuyên sâu', 'gia_goc' => 1500000],
                ['ten' => 'Thu âm thanh môi trường', 'gia_goc' => 900000],
                ['ten' => 'Nhạc bản quyền cho phim', 'gia_goc' => 700000],
                ['ten' => 'Team 2 cameraman', 'gia_goc' => 3000000],
                ['ten' => 'Quay phim after wedding', 'gia_goc' => 4000000],
                ['ten' => 'USB / ổ cứng bàn giao', 'gia_goc' => 500000],
                ['ten' => 'Xuất bản Youtube / drive', 'gia_goc' => 300000],
                ['ten' => 'Quay phim đa góc (multi-cam)', 'gia_goc' => 5200000],
            ],
            'combo' => [
                ['ten' => 'Combo highlight ngày cưới', 'dich_vu_indexes' => [0, 1, 10], 'so_diem_chup' => 0, 'so_anh_chinh_sua' => 0],
                ['ten' => 'Combo phóng sự tiêu chuẩn', 'dich_vu_indexes' => [0, 11, 13], 'so_diem_chup' => 0, 'so_anh_chinh_sua' => 0],
                ['ten' => 'Combo cinematic premium', 'dich_vu_indexes' => [0, 5, 10, 12, 14], 'so_diem_chup' => 0, 'so_anh_chinh_sua' => 0],
                ['ten' => 'Combo pre-wedding film', 'dich_vu_indexes' => [2, 4, 10], 'so_diem_chup' => 0, 'so_anh_chinh_sua' => 0],
                ['ten' => 'Combo ăn hỏi + cưới', 'dich_vu_indexes' => [0, 6], 'so_diem_chup' => 0, 'so_anh_chinh_sua' => 0],
                ['ten' => 'Combo nhà thờ + tiệc', 'dich_vu_indexes' => [7, 8, 1], 'so_diem_chup' => 0, 'so_anh_chinh_sua' => 0],
                ['ten' => 'Combo livestream', 'dich_vu_indexes' => [9, 15], 'so_diem_chup' => 0, 'so_anh_chinh_sua' => 0],
                ['ten' => 'Combo flycam + multi-cam', 'dich_vu_indexes' => [5, 19, 15], 'so_diem_chup' => 0, 'so_anh_chinh_sua' => 0],
                ['ten' => 'Combo after wedding', 'dich_vu_indexes' => [16, 10, 17], 'so_diem_chup' => 0, 'so_anh_chinh_sua' => 0],
                ['ten' => 'Combo trọn gói quay phim', 'dich_vu_indexes' => [0, 1, 5, 10, 12, 15, 17], 'so_diem_chup' => 0, 'so_anh_chinh_sua' => 0],
            ],
        ];
    }

    /**
     * @return array{loai_dich_vu: string, dich_vu: list<array<string, mixed>>, combo: list<array<string, mixed>>}
     */
    private function catalogAlbum(): array
    {
        return [
            'loai_dich_vu' => 'Album',
            'dich_vu' => [
                ['ten' => 'Album photobook 20x20 (20 trang)', 'gia_goc' => 1800000],
                ['ten' => 'Album photobook 20x20 (30 trang)', 'gia_goc' => 2400000],
                ['ten' => 'Album photobook 25x25 (30 trang)', 'gia_goc' => 3200000],
                ['ten' => 'Album photobook 30x30 (40 trang)', 'gia_goc' => 4500000],
                ['ten' => 'Album truyền thống ép gỗ', 'gia_goc' => 3800000],
                ['ten' => 'Album mica cao cấp', 'gia_goc' => 4200000],
                ['ten' => 'Album da bò thật', 'gia_goc' => 5500000],
                ['ten' => 'Mini album để bàn', 'gia_goc' => 900000],
                ['ten' => 'Parent album (album phụ huynh)', 'gia_goc' => 1600000],
                ['ten' => 'Thiết kế layout album', 'gia_goc' => 1200000],
                ['ten' => 'In ảnh để bàn 13x18 (30 tấm)', 'gia_goc' => 700000],
                ['ten' => 'In ảnh phóng 40x60', 'gia_goc' => 850000],
                ['ten' => 'Ảnh canvas 50x75', 'gia_goc' => 1400000],
                ['ten' => 'Khung ảnh composite', 'gia_goc' => 1100000],
                ['ten' => 'USB in khắc tên', 'gia_goc' => 450000],
                ['ten' => 'Hộp đựng album gỗ', 'gia_goc' => 800000],
                ['ten' => 'Thêm trang album', 'gia_goc' => 150000],
                ['ten' => 'Đổi bìa album cao cấp', 'gia_goc' => 600000],
                ['ten' => 'Giao album tận nhà', 'gia_goc' => 200000],
                ['ten' => 'In album gấp (rush fee)', 'gia_goc' => 1000000],
            ],
            'combo' => [
                ['ten' => 'Combo album cơ bản', 'dich_vu_indexes' => [0, 9], 'so_diem_chup' => 0, 'so_anh_chinh_sua' => 20],
                ['ten' => 'Combo album tiêu chuẩn', 'dich_vu_indexes' => [1, 9, 15], 'so_diem_chup' => 0, 'so_anh_chinh_sua' => 30],
                ['ten' => 'Combo album lớn', 'dich_vu_indexes' => [3, 9, 15], 'so_diem_chup' => 0, 'so_anh_chinh_sua' => 40],
                ['ten' => 'Combo album da bò', 'dich_vu_indexes' => [6, 9, 15], 'so_diem_chup' => 0, 'so_anh_chinh_sua' => 40],
                ['ten' => 'Combo album + parent', 'dich_vu_indexes' => [2, 8, 9], 'so_diem_chup' => 0, 'so_anh_chinh_sua' => 30],
                ['ten' => 'Combo in ấn để bàn', 'dich_vu_indexes' => [7, 10, 13], 'so_diem_chup' => 0, 'so_anh_chinh_sua' => 0],
                ['ten' => 'Combo treo tường', 'dich_vu_indexes' => [11, 12], 'so_diem_chup' => 0, 'so_anh_chinh_sua' => 0],
                ['ten' => 'Combo lưu trữ số', 'dich_vu_indexes' => [14, 9], 'so_diem_chup' => 0, 'so_anh_chinh_sua' => 0],
                ['ten' => 'Combo album mica premium', 'dich_vu_indexes' => [5, 9, 15, 17], 'so_diem_chup' => 0, 'so_anh_chinh_sua' => 35],
                ['ten' => 'Combo trọn gói album cưới', 'dich_vu_indexes' => [3, 7, 8, 9, 10, 14, 15], 'so_diem_chup' => 0, 'so_anh_chinh_sua' => 50],
            ],
        ];
    }
};
