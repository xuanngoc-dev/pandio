<?php

use App\Models\Concept;
use App\Models\DanhMucConcept;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const SEED_PREFIX = 'SEED_CT_';

    private const SEED_MARKER_DM_CONCEPT = 'SEED_DM_CONCEPT';

    public function up(): void
    {
        $loaiIds = DanhMucConcept::query()
            ->where('mo_ta', 'like', self::SEED_MARKER_DM_CONCEPT.'%')
            ->orderBy('id')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($loaiIds === []) {
            $loaiIds = DanhMucConcept::query()
                ->orderBy('id')
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();
        }

        if ($loaiIds === []) {
            return;
        }

        $trangThai = ['dang_su_dung', 'ngung_su_dung'];
        $now = now();

        DB::transaction(function () use ($loaiIds, $trangThai, $now) {
            foreach ($this->conceptCatalog() as $index => $item) {
                $stt = str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT);
                $maConcept = self::SEED_PREFIX.$stt;

                Concept::query()->updateOrCreate(
                    ['ma_concept' => $maConcept],
                    [
                        'hinh_anh' => null,
                        'loai_concept' => $loaiIds[$index % count($loaiIds)],
                        'ten_concept' => $item['ten_concept'],
                        'dia_diem' => $item['dia_diem'],
                        'trang_thai' => $trangThai[$index % 10 === 0 ? 1 : 0],
                        'mo_ta' => $item['mo_ta'],
                        'updated_at' => $now,
                        'created_at' => $now,
                    ]
                );
            }
        });
    }

    public function down(): void
    {
        Concept::query()
            ->where('ma_concept', 'like', self::SEED_PREFIX.'%')
            ->delete();
    }

    /**
     * @return list<array{ten_concept: string, dia_diem: string, mo_ta: string}>
     */
    private function conceptCatalog(): array
    {
        return [
            ['ten_concept' => 'Soft White Studio', 'dia_diem' => 'Studio Thanh Xuân', 'mo_ta' => 'Phông trắng tối giản, softbox'],
            ['ten_concept' => 'Black Cinematic', 'dia_diem' => 'Studio Thanh Xuân', 'mo_ta' => 'Tone tối, spotlight mạnh'],
            ['ten_concept' => 'Satin Drape', 'dia_diem' => 'Studio Cầu Giấy', 'mo_ta' => 'Vải satin rủ, ánh sáng vàng'],
            ['ten_concept' => 'Fresh Flower Wall', 'dia_diem' => 'Studio Cầu Giấy', 'mo_ta' => 'Backdrop hoa tươi theo mùa'],
            ['ten_concept' => 'European Classic', 'dia_diem' => 'Phòng cổ điển', 'mo_ta' => 'Nội thất vintage, khung tranh vàng'],
            ['ten_concept' => 'Korean Cafe Soft', 'dia_diem' => 'Cafe Hàn Quốc', 'mo_ta' => 'Tone pastel, cửa sổ lớn'],
            ['ten_concept' => 'Japanese Garden', 'dia_diem' => 'Vườn Nhật', 'mo_ta' => 'Sân vườn thiền, đá và nước'],
            ['ten_concept' => 'Chinese Lantern Night', 'dia_diem' => 'Phố đèn lồng', 'mo_ta' => 'Đèn lồng đỏ, tường vàng'],
            ['ten_concept' => 'Áo dài gấm đỏ', 'dia_diem' => 'Studio nội thất', 'mo_ta' => 'Áo dài truyền thống đỏ gấm'],
            ['ten_concept' => 'Áo dài cách tân trắng', 'dia_diem' => 'Studio nội thất', 'mo_ta' => 'Áo dài hiện đại tối giản'],
            ['ten_concept' => 'Rose Garden Morning', 'dia_diem' => 'Vườn hồng Đà Lạt', 'mo_ta' => 'Outdoor vườn hoa buổi sáng'],
            ['ten_concept' => 'Beach Sunrise', 'dia_diem' => 'Bãi biển Đà Nẵng', 'mo_ta' => 'Bình minh, cát trắng'],
            ['ten_concept' => 'Beach Golden Hour', 'dia_diem' => 'Bãi biển Nha Trang', 'mo_ta' => 'Hoàng hôn ven biển'],
            ['ten_concept' => 'Foggy Mountain', 'dia_diem' => 'Sapa', 'mo_ta' => 'Đồi núi sương sớm'],
            ['ten_concept' => 'Lotus Lake Summer', 'dia_diem' => 'Hồ sen Hà Nội', 'mo_ta' => 'Sen nở, ánh sáng tự nhiên'],
            ['ten_concept' => 'Terraced Fields', 'dia_diem' => 'Mù Cang Chải', 'mo_ta' => 'Ruộng bậc thang mùa lúa'],
            ['ten_concept' => 'Hoi An Old Town', 'dia_diem' => 'Phố cổ Hội An', 'mo_ta' => 'Đèn lồng, tường vàng'],
            ['ten_concept' => 'Hanoi Old Quarter', 'dia_diem' => 'Phố cổ Hà Nội', 'mo_ta' => 'Góc phố, xe đạp'],
            ['ten_concept' => 'Cathedral Steps', 'dia_diem' => 'Nhà thờ Lớn Hà Nội', 'mo_ta' => 'Kiến trúc Gothic'],
            ['ten_concept' => 'Castle Gate', 'dia_diem' => 'Lâu đài Bà Nà', 'mo_ta' => 'Cổng vòm, cầu thang đá'],
            ['ten_concept' => 'Five Star Lobby', 'dia_diem' => 'Khách sạn 5 sao', 'mo_ta' => 'Sảnh lớn, cầu thang xoắn'],
            ['ten_concept' => 'City Rooftop Night', 'dia_diem' => 'Rooftop trung tâm', 'mo_ta' => 'Skyline về đêm'],
            ['ten_concept' => 'Vintage Cafe Corner', 'dia_diem' => 'Cafe gỗ cổ', 'mo_ta' => 'Nội thất gỗ, đèn vàng'],
            ['ten_concept' => 'Old Library Light', 'dia_diem' => 'Thư viện cổ', 'mo_ta' => 'Ánh sáng cửa sổ, kệ sách'],
            ['ten_concept' => 'Piano Room', 'dia_diem' => 'Phòng nhạc', 'mo_ta' => 'Đàn piano, ánh sáng dịu'],
            ['ten_concept' => 'Art Deco Glam', 'dia_diem' => 'Studio glam', 'mo_ta' => 'Kim loại vàng, gương'],
            ['ten_concept' => 'Boho Picnic', 'dia_diem' => 'Công viên ngoài trời', 'mo_ta' => 'Thảm, hoa khô, lều'],
            ['ten_concept' => 'Urban Minimal', 'dia_diem' => 'Khu đô thị mới', 'mo_ta' => 'Beton, đường phố sạch'],
            ['ten_concept' => 'Neon Alley', 'dia_diem' => 'Phố đêm', 'mo_ta' => 'Đèn neon, phản chiếu'],
            ['ten_concept' => 'Film Noir BW', 'dia_diem' => 'Studio đen trắng', 'mo_ta' => 'Cinematic đen trắng'],
            ['ten_concept' => 'Dreamy Pastel Room', 'dia_diem' => 'Studio pastel', 'mo_ta' => 'Tone hồng mint'],
            ['ten_concept' => 'Autumn Park', 'dia_diem' => 'Công viên lá vàng', 'mo_ta' => 'Mùa thu, lá rơi'],
            ['ten_concept' => 'Snowy Winter', 'dia_diem' => 'Đà Lạt mùa lạnh', 'mo_ta' => 'Áo choàng, sương lạnh'],
            ['ten_concept' => 'Cherry Blossom Path', 'dia_diem' => 'Con đường hoa anh đào', 'mo_ta' => 'Hoa anh đào nở'],
            ['ten_concept' => 'Lavender Field Walk', 'dia_diem' => 'Cánh đồng oải hương', 'mo_ta' => 'Tím lavender, gió nhẹ'],
            ['ten_concept' => 'Sunflower Farm', 'dia_diem' => 'Nông trại hướng dương', 'mo_ta' => 'Vàng nắng, cánh đồng rộng'],
            ['ten_concept' => 'Tea Ceremony House', 'dia_diem' => 'Trà thất', 'mo_ta' => 'Không gian trà đạo Nhật'],
            ['ten_concept' => 'Greenhouse Garden', 'dia_diem' => 'Nhà kính', 'mo_ta' => 'Cây xanh, ánh sáng lọc'],
            ['ten_concept' => 'Ballroom Waltz', 'dia_diem' => 'Sảnh khiêu vũ', 'mo_ta' => 'Sàn bóng, đèn chùm'],
            ['ten_concept' => 'Royal Red Carpet', 'dia_diem' => 'Cung điện / sảnh lớn', 'mo_ta' => 'Thảm đỏ, cột đá'],
            ['ten_concept' => 'River Yacht', 'dia_diem' => 'Du thuyền sông Hồng', 'mo_ta' => 'Boong tàu, gió sông'],
            ['ten_concept' => 'Hangar Industrial', 'dia_diem' => 'Nhà chứa máy bay', 'mo_ta' => 'Industrial, máy bay'],
            ['ten_concept' => 'Vintage Train Station', 'dia_diem' => 'Ga tàu cổ', 'mo_ta' => 'Ga xưa, đường ray'],
            ['ten_concept' => 'Art Gallery Walls', 'dia_diem' => 'Gallery nghệ thuật', 'mo_ta' => 'Tranh lớn, tường trắng'],
            ['ten_concept' => 'Waterfall Mist', 'dia_diem' => 'Thác nước', 'mo_ta' => 'Sương mù, đá ướt'],
            ['ten_concept' => 'Desert Dune Soft', 'dia_diem' => 'Đồi cát', 'mo_ta' => 'Cát vàng, gió'],
            ['ten_concept' => 'Mountain Cabin', 'dia_diem' => 'Nhà gỗ trên núi', 'mo_ta' => 'Gỗ ấm, sương núi'],
            ['ten_concept' => 'Classic Car Prewedding', 'dia_diem' => 'Studio + xe cổ', 'mo_ta' => 'Xe cổ, concept retro'],
            ['ten_concept' => 'Family Picnic Green', 'dia_diem' => 'Công viên gia đình', 'mo_ta' => 'Gia đình ngoài trời'],
            ['ten_concept' => 'Maternity Soft Glow', 'dia_diem' => 'Studio ánh sáng dịu', 'mo_ta' => 'Bầu bí, soft light'],
        ];
    }
};
