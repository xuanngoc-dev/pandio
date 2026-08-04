<?php

use App\Models\CauHinhChiNhanh;
use App\Models\DanhMucConcept;
use App\Models\DanhMucTrangPhuc;
use App\Models\NhaCungCapTrangPhuc;
use App\Models\TrangPhuc;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const SEED_PREFIX_TP = 'SEED_TP_';

    private const SEED_PREFIX_DM_TP = 'SEED_DMTP_';

    private const SEED_PREFIX_NCC = 'SEED_NCC_';

    private const SEED_MARKER_DM_CONCEPT = 'SEED_DM_CONCEPT';

    public function up(): void
    {
        DB::transaction(function () {
            $this->seedDanhMucConcept();
            $this->seedTrangPhuc();
        });
    }

    public function down(): void
    {
        TrangPhuc::query()
            ->where('ma_san_pham', 'like', self::SEED_PREFIX_TP.'%')
            ->delete();

        DanhMucConcept::query()
            ->where('mo_ta', 'like', self::SEED_MARKER_DM_CONCEPT.'%')
            ->delete();

        DanhMucTrangPhuc::query()
            ->where('ma_danh_muc', 'like', self::SEED_PREFIX_DM_TP.'%')
            ->delete();

        NhaCungCapTrangPhuc::query()
            ->where('ma_nha_cung_cap', 'like', self::SEED_PREFIX_NCC.'%')
            ->delete();
    }

    private function seedDanhMucConcept(): void
    {
        $now = now();

        foreach ($this->danhMucConceptCatalog() as $item) {
            DanhMucConcept::query()->updateOrCreate(
                ['ten_danh_muc' => $item['ten_danh_muc']],
                [
                    'mo_ta' => self::SEED_MARKER_DM_CONCEPT.'|'.$item['mo_ta'],
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
        }
    }

    private function seedTrangPhuc(): void
    {
        $danhMucIds = $this->ensureDanhMucTrangPhuc();
        $nccIds = $this->ensureNhaCungCap();
        $chiNhanhIds = CauHinhChiNhanh::query()->orderBy('id')->pluck('id')->all();

        if ($chiNhanhIds === []) {
            return;
        }

        $phanLoai = ['dau_tu_tai_san', 'vat_tu_tieu_hao'];
        $tinhTrang = ['con_hang', 'dang_cho_thue', 'dang_sua_chua', 'ngung_su_dung'];
        $now = now();

        foreach ($this->trangPhucCatalog() as $index => $item) {
            $stt = str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT);
            $maSanPham = self::SEED_PREFIX_TP.$stt;
            $giaTri = (int) $item['gia_tri'];
            $giaChoThue = (int) ($item['gia_cho_thue'] ?? (int) round($giaTri * 0.08));

            TrangPhuc::query()->updateOrCreate(
                ['ma_san_pham' => $maSanPham],
                [
                    'hinh_anh' => null,
                    'ten_san_pham' => $item['ten_san_pham'],
                    'danh_muc' => $danhMucIds[$index % count($danhMucIds)],
                    'nha_cung_cap' => $nccIds[$index % count($nccIds)],
                    'chi_nhanh' => $chiNhanhIds[$index % count($chiNhanhIds)],
                    'gia_tri' => $giaTri,
                    'gia_cho_thue' => $giaChoThue,
                    'phan_loai_chi_phi' => $phanLoai[$index % count($phanLoai)],
                    'tinh_trang' => $tinhTrang[$index % count($tinhTrang)],
                    'ghi_chu' => 'Dữ liệu mẫu migrate trang phục',
                    'trang_thai' => $index % 10 === 0 ? 0 : 1,
                    'thong_tin_them' => [
                        [
                            'ten_thuoc_tinh' => 'Size',
                            'gia_tri' => $item['size'] ?? 'M',
                            'ghi_chu' => null,
                        ],
                        [
                            'ten_thuoc_tinh' => 'Màu sắc',
                            'gia_tri' => $item['mau'] ?? 'Trắng',
                            'ghi_chu' => null,
                        ],
                    ],
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
        }
    }

    /**
     * @return list<int>
     */
    private function ensureDanhMucTrangPhuc(): array
    {
        $catalog = [
            ['ma' => 'VC', 'ten' => 'Váy cưới', 'mo_ta' => 'Váy cưới các kiểu'],
            ['ma' => 'AO_DAI', 'ten' => 'Áo dài', 'mo_ta' => 'Áo dài cô dâu / chú rể'],
            ['ma' => 'VEST', 'ten' => 'Vest chú rể', 'mo_ta' => 'Vest, tuxedo'],
            ['ma' => 'AO_DAI_AN', 'ten' => 'Áo dài ăn hỏi', 'mo_ta' => 'Áo dài lễ ăn hỏi'],
            ['ma' => 'TRAD', 'ten' => 'Trang phục truyền thống', 'mo_ta' => 'Trang phục dân tộc / truyền thống'],
            ['ma' => 'HANBOK', 'ten' => 'Hanbok / Hàn Quốc', 'mo_ta' => 'Concept Hàn'],
            ['ma' => 'PHU_KIEN', 'ten' => 'Phụ kiện', 'mo_ta' => 'Voan, găng tay, cà vạt...'],
            ['ma' => 'DOI_BAN', 'ten' => 'Đồ đôi / cặp', 'mo_ta' => 'Trang phục cặp đôi'],
        ];

        $ids = [];

        foreach ($catalog as $item) {
            $existing = DanhMucTrangPhuc::query()
                ->where('ma_danh_muc', $item['ma'])
                ->orWhere('ma_danh_muc', self::SEED_PREFIX_DM_TP.$item['ma'])
                ->first();

            if ($existing) {
                $ids[] = (int) $existing->id;

                continue;
            }

            $created = DanhMucTrangPhuc::query()->create([
                'ma_danh_muc' => self::SEED_PREFIX_DM_TP.$item['ma'],
                'ten_danh_muc' => $item['ten'],
                'mo_ta' => $item['mo_ta'],
            ]);
            $ids[] = (int) $created->id;
        }

        $existingIds = DanhMucTrangPhuc::query()->orderBy('id')->pluck('id')->all();

        return $existingIds !== [] ? array_map('intval', $existingIds) : $ids;
    }

    /**
     * @return list<int>
     */
    private function ensureNhaCungCap(): array
    {
        $catalog = [
            ['ma' => 'NCC01', 'ten' => 'Xưởng may Pandio', 'sdt' => '0901000001'],
            ['ma' => 'NCC02', 'ten' => 'Atelier Cô Dâu', 'sdt' => '0901000002'],
            ['ma' => 'NCC03', 'ten' => 'Vest House Hà Nội', 'sdt' => '0901000003'],
            ['ma' => 'NCC04', 'ten' => 'Áo Dài Huế Sài Gòn', 'sdt' => '0901000004'],
        ];

        foreach ($catalog as $item) {
            $exists = NhaCungCapTrangPhuc::query()
                ->where('ma_nha_cung_cap', $item['ma'])
                ->orWhere('ma_nha_cung_cap', self::SEED_PREFIX_NCC.$item['ma'])
                ->exists();

            if ($exists) {
                continue;
            }

            NhaCungCapTrangPhuc::query()->create([
                'ma_nha_cung_cap' => self::SEED_PREFIX_NCC.$item['ma'],
                'ten_nha_cung_cap' => $item['ten'],
                'so_dien_thoai' => $item['sdt'],
                'dia_chi' => 'Hà Nội',
                'email' => strtolower($item['ma']).'@seed.local',
                'ghi_chu' => 'Nhà cung cấp mẫu migrate',
            ]);
        }

        return NhaCungCapTrangPhuc::query()
            ->orderBy('id')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * @return list<array{ten_danh_muc: string, mo_ta: string}>
     */
    private function danhMucConceptCatalog(): array
    {
        return [
            ['ten_danh_muc' => 'Studio trắng tối giản', 'mo_ta' => 'Phông trắng, ánh sáng softbox'],
            ['ten_danh_muc' => 'Studio đen cinematic', 'mo_ta' => 'Tone tối, spotlight'],
            ['ten_danh_muc' => 'Phông vải satin', 'mo_ta' => 'Vải rủ, ánh sáng vàng'],
            ['ten_danh_muc' => 'Phông hoa tươi', 'mo_ta' => 'Backdrop hoa tươi theo mùa'],
            ['ten_danh_muc' => 'Concept cổ điển châu Âu', 'mo_ta' => 'Nội thất vintage, khung tranh'],
            ['ten_danh_muc' => 'Concept Hàn Quốc', 'mo_ta' => 'Tone pastel, cafe Hàn'],
            ['ten_danh_muc' => 'Concept Nhật Bản', 'mo_ta' => 'Vườn thiền, kimono nhẹ'],
            ['ten_danh_muc' => 'Concept Trung Hoa', 'mo_ta' => 'Áo dài Tàu, đèn lồng'],
            ['ten_danh_muc' => 'Áo dài truyền thống', 'mo_ta' => 'Áo dài đỏ / vàng kim'],
            ['ten_danh_muc' => 'Áo dài hiện đại', 'mo_ta' => 'Áo dài cách tân'],
            ['ten_danh_muc' => 'Vườn hoa hồng', 'mo_ta' => 'Outdoor vườn hoa'],
            ['ten_danh_muc' => 'Bãi biển bình minh', 'mo_ta' => 'Biển, cát, ánh nắng sớm'],
            ['ten_danh_muc' => 'Bãi biển hoàng hôn', 'mo_ta' => 'Golden hour ven biển'],
            ['ten_danh_muc' => 'Núi rừng sương sớm', 'mo_ta' => 'Đồi, sương, ánh sáng lạnh'],
            ['ten_danh_muc' => 'Hồ sen mùa hạ', 'mo_ta' => 'Sen nở, áo dài'],
            ['ten_danh_muc' => 'Ruộng bậc thang', 'mo_ta' => 'Tây Bắc, trang phục dân tộc'],
            ['ten_danh_muc' => 'Phố cổ Hội An', 'mo_ta' => 'Đèn lồng, tường vàng'],
            ['ten_danh_muc' => 'Phố cổ Hà Nội', 'mo_ta' => 'Góc phố, xe đạp'],
            ['ten_danh_muc' => 'Nhà thờ lớn', 'mo_ta' => 'Kiến trúc Gothic'],
            ['ten_danh_muc' => 'Lâu đài châu Âu', 'mo_ta' => 'Cổng vòm, cầu thang đá'],
            ['ten_danh_muc' => 'Khách sạn 5 sao', 'mo_ta' => 'Sảnh lớn, cầu thang xoắn'],
            ['ten_danh_muc' => 'Rooftop thành phố', 'mo_ta' => 'Skyline về đêm'],
            ['ten_danh_muc' => 'Cafe vintage', 'mo_ta' => 'Nội thất gỗ, ánh đèn vàng'],
            ['ten_danh_muc' => 'Thư viện cổ', 'mo_ta' => 'Kệ sách, ánh sáng cửa sổ'],
            ['ten_danh_muc' => 'Piano & nhạc cổ điển', 'mo_ta' => 'Phòng nhạc, đàn piano'],
            ['ten_danh_muc' => 'Art deco glam', 'mo_ta' => 'Kim loại vàng, gương'],
            ['ten_danh_muc' => 'Bohemian outdoor', 'mo_ta' => 'Thảm, hoa khô, lều'],
            ['ten_danh_muc' => 'Minimalist urban', 'mo_ta' => 'Beton, đường phố hiện đại'],
            ['ten_danh_muc' => 'Neon night', 'mo_ta' => 'Đèn neon, phố đêm'],
            ['ten_danh_muc' => 'Film noir', 'mo_ta' => 'Đen trắng cinematic'],
            ['ten_danh_muc' => 'Dreamy pastel', 'mo_ta' => 'Tone hồng / xanh mint'],
            ['ten_danh_muc' => 'Autumn leaves', 'mo_ta' => 'Lá vàng, công viên'],
            ['ten_danh_muc' => 'Winter snow', 'mo_ta' => 'Tuyết, áo choàng'],
            ['ten_danh_muc' => 'Cherry blossom', 'mo_ta' => 'Hoa anh đào'],
            ['ten_danh_muc' => 'Lavender field', 'mo_ta' => 'Cánh đồng oải hương'],
            ['ten_danh_muc' => 'Sunflower farm', 'mo_ta' => 'Cánh đồng hướng dương'],
            ['ten_danh_muc' => 'Tea house', 'mo_ta' => 'Trà đạo, không gian Nhật'],
            ['ten_danh_muc' => 'Garden greenhouse', 'mo_ta' => 'Nhà kính, cây xanh'],
            ['ten_danh_muc' => 'Ballroom dance', 'mo_ta' => 'Sảnh khiêu vũ'],
            ['ten_danh_muc' => 'Royal palace', 'mo_ta' => 'Cung điện, thảm đỏ'],
            ['ten_danh_muc' => 'River cruise', 'mo_ta' => 'Du thuyền sông'],
            ['ten_danh_muc' => 'Airplane hangar', 'mo_ta' => 'Máy bay, industrial'],
            ['ten_danh_muc' => 'Train station', 'mo_ta' => 'Ga tàu cổ'],
            ['ten_danh_muc' => 'Museum gallery', 'mo_ta' => 'Bảo tàng, tranh nghệ thuật'],
            ['ten_danh_muc' => 'Waterfall mist', 'mo_ta' => 'Thác nước, sương'],
            ['ten_danh_muc' => 'Desert dunes', 'mo_ta' => 'Cát vàng, gió'],
            ['ten_danh_muc' => 'Mountain cabin', 'mo_ta' => 'Nhà gỗ trên núi'],
            ['ten_danh_muc' => 'Pre-wedding xe cổ', 'mo_ta' => 'Xe cổ, concept retro'],
            ['ten_danh_muc' => 'Family outdoor', 'mo_ta' => 'Gia đình ngoài trời'],
            ['ten_danh_muc' => 'Maternity soft light', 'mo_ta' => 'Bầu bí, ánh sáng dịu'],
        ];
    }

    /**
     * @return list<array{ten_san_pham: string, gia_tri: int, gia_cho_thue?: int, size?: string, mau?: string}>
     */
    private function trangPhucCatalog(): array
    {
        return [
            ['ten_san_pham' => 'Váy cưới A-line trắng cơ bản', 'gia_tri' => 8500000, 'size' => 'S', 'mau' => 'Trắng'],
            ['ten_san_pham' => 'Váy cưới đuôi cá pha lê', 'gia_tri' => 15000000, 'size' => 'M', 'mau' => 'Trắng ngà'],
            ['ten_san_pham' => 'Váy cưới công chúa tầng', 'gia_tri' => 18000000, 'size' => 'M', 'mau' => 'Trắng'],
            ['ten_san_pham' => 'Váy cưới chữ A voan mỏng', 'gia_tri' => 9500000, 'size' => 'S', 'mau' => 'Trắng sữa'],
            ['ten_san_pham' => 'Váy cưới lệch vai đính hạt', 'gia_tri' => 12500000, 'size' => 'M', 'mau' => 'Trắng'],
            ['ten_san_pham' => 'Váy cưới cổ V sâu', 'gia_tri' => 11000000, 'size' => 'L', 'mau' => 'Trắng'],
            ['ten_san_pham' => 'Váy cưới ngắn tea-length', 'gia_tri' => 7200000, 'size' => 'S', 'mau' => 'Kem'],
            ['ten_san_pham' => 'Váy cưới satin bóng tối giản', 'gia_tri' => 13500000, 'size' => 'M', 'mau' => 'Trắng'],
            ['ten_san_pham' => 'Áo dài cô dâu đỏ gấm', 'gia_tri' => 5500000, 'size' => 'M', 'mau' => 'Đỏ'],
            ['ten_san_pham' => 'Áo dài cô dâu vàng kim', 'gia_tri' => 5800000, 'size' => 'S', 'mau' => 'Vàng'],
            ['ten_san_pham' => 'Áo dài cách tân trắng', 'gia_tri' => 4200000, 'size' => 'M', 'mau' => 'Trắng'],
            ['ten_san_pham' => 'Áo dài cặp đỏ đô', 'gia_tri' => 9000000, 'size' => 'M', 'mau' => 'Đỏ đô'],
            ['ten_san_pham' => 'Áo dài chú rể xanh navy', 'gia_tri' => 3800000, 'size' => 'L', 'mau' => 'Xanh navy'],
            ['ten_san_pham' => 'Áo dài ăn hỏi hồng pastel', 'gia_tri' => 4800000, 'size' => 'S', 'mau' => 'Hồng'],
            ['ten_san_pham' => 'Áo dài ăn hỏi xanh ngọc', 'gia_tri' => 4900000, 'size' => 'M', 'mau' => 'Xanh ngọc'],
            ['ten_san_pham' => 'Vest chú rể đen classic', 'gia_tri' => 6500000, 'size' => 'L', 'mau' => 'Đen'],
            ['ten_san_pham' => 'Vest chú rể xám chì', 'gia_tri' => 6200000, 'size' => 'M', 'mau' => 'Xám'],
            ['ten_san_pham' => 'Tuxedo đen nơ trắng', 'gia_tri' => 7800000, 'size' => 'L', 'mau' => 'Đen'],
            ['ten_san_pham' => 'Vest xanh midnight', 'gia_tri' => 7000000, 'size' => 'M', 'mau' => 'Xanh đen'],
            ['ten_san_pham' => 'Vest trắng sự kiện', 'gia_tri' => 6800000, 'size' => 'L', 'mau' => 'Trắng'],
            ['ten_san_pham' => 'Vest kẻ caro nâu', 'gia_tri' => 5500000, 'size' => 'M', 'mau' => 'Nâu'],
            ['ten_san_pham' => 'Hanbok hồng pastel', 'gia_tri' => 4500000, 'size' => 'S', 'mau' => 'Hồng'],
            ['ten_san_pham' => 'Hanbok xanh mint', 'gia_tri' => 4500000, 'size' => 'M', 'mau' => 'Xanh mint'],
            ['ten_san_pham' => 'Kimono nhẹ sakura', 'gia_tri' => 5200000, 'size' => 'M', 'mau' => 'Hồng nhạt'],
            ['ten_san_pham' => 'Sườn xám đỏ thêu', 'gia_tri' => 5100000, 'size' => 'S', 'mau' => 'Đỏ'],
            ['ten_san_pham' => 'Trang phục dân tộc Thái', 'gia_tri' => 3900000, 'size' => 'M', 'mau' => 'Đen bạc'],
            ['ten_san_pham' => 'Trang phục H\'Mông màu sắc', 'gia_tri' => 4100000, 'size' => 'S', 'mau' => 'Nhiều màu'],
            ['ten_san_pham' => 'Váy cocktail đen glam', 'gia_tri' => 3600000, 'size' => 'M', 'mau' => 'Đen'],
            ['ten_san_pham' => 'Váy dạ hội xanh cobalt', 'gia_tri' => 8800000, 'size' => 'M', 'mau' => 'Xanh'],
            ['ten_san_pham' => 'Váy dạ hội đỏ rượu', 'gia_tri' => 9200000, 'size' => 'L', 'mau' => 'Đỏ rượu'],
            ['ten_san_pham' => 'Đầm cưới thứ hai champagne', 'gia_tri' => 6400000, 'size' => 'S', 'mau' => 'Champagne'],
            ['ten_san_pham' => 'Set đồ đôi linen be', 'gia_tri' => 3200000, 'size' => 'M', 'mau' => 'Be'],
            ['ten_san_pham' => 'Set đồ đôi trắng tối giản', 'gia_tri' => 3500000, 'size' => 'L', 'mau' => 'Trắng'],
            ['ten_san_pham' => 'Áo choàng cưới lông vũ', 'gia_tri' => 2800000, 'size' => 'Free', 'mau' => 'Trắng'],
            ['ten_san_pham' => 'Voan cưới dài 3m', 'gia_tri' => 1500000, 'size' => 'Free', 'mau' => 'Trắng'],
            ['ten_san_pham' => 'Voan cưới ngắn đính hạt', 'gia_tri' => 1200000, 'size' => 'Free', 'mau' => 'Trắng'],
            ['ten_san_pham' => 'Găng tay cưới ren', 'gia_tri' => 450000, 'size' => 'Free', 'mau' => 'Trắng'],
            ['ten_san_pham' => 'Cà vạt chú rể lụa', 'gia_tri' => 350000, 'size' => 'Free', 'mau' => 'Đỏ'],
            ['ten_san_pham' => 'Nơ tuxedo đen', 'gia_tri' => 280000, 'size' => 'Free', 'mau' => 'Đen'],
            ['ten_san_pham' => 'Khăn choàng lụa vàng', 'gia_tri' => 680000, 'size' => 'Free', 'mau' => 'Vàng'],
            ['ten_san_pham' => 'Váy cưới plus-size A-line', 'gia_tri' => 9800000, 'size' => 'XL', 'mau' => 'Trắng'],
            ['ten_san_pham' => 'Váy cưới trẻ em flower girl', 'gia_tri' => 2200000, 'size' => 'Kid', 'mau' => 'Trắng'],
            ['ten_san_pham' => 'Vest nhí chú rể nhỏ', 'gia_tri' => 1800000, 'size' => 'Kid', 'mau' => 'Đen'],
            ['ten_san_pham' => 'Áo dài bà sui đỏ', 'gia_tri' => 4300000, 'size' => 'XL', 'mau' => 'Đỏ'],
            ['ten_san_pham' => 'Áo dài ông sui xanh', 'gia_tri' => 4100000, 'size' => 'XL', 'mau' => 'Xanh'],
            ['ten_san_pham' => 'Váy cưới bohemian outdoor', 'gia_tri' => 7600000, 'size' => 'M', 'mau' => 'Kem'],
            ['ten_san_pham' => 'Váy cưới mermaid sequin', 'gia_tri' => 16500000, 'size' => 'S', 'mau' => 'Champagne'],
            ['ten_san_pham' => 'Váy cưới ballgown crystal', 'gia_tri' => 22000000, 'size' => 'M', 'mau' => 'Trắng'],
            ['ten_san_pham' => 'Áo dài thiết kế thêu tay', 'gia_tri' => 7500000, 'size' => 'M', 'mau' => 'Hồng sen'],
            ['ten_san_pham' => 'Vest 3 mảnh charcoal', 'gia_tri' => 8200000, 'size' => 'L', 'mau' => 'Xám đậm'],
        ];
    }
};
