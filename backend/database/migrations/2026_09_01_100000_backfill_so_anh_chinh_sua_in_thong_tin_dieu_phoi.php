<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const SO_ANH_CHINH_SUA_KEY = 'so_anh_chinh_sua';

    public function up(): void
    {
        $totals = DB::table('hop_dong_dong_sddv_combos as hc')
            ->join('dich_vu_danh_sach_dich_nhom_dich_vu as c', 'c.id', '=', 'hc.combo_id')
            ->selectRaw('hc.ma_hop_dong_id as hop_dong_id, SUM(c.so_anh_chinh_sua * hc.so_luong) as total')
            ->groupBy('hc.ma_hop_dong_id')
            ->pluck('total', 'hop_dong_id');

        if ($totals->isEmpty()) {
            return;
        }

        $rows = DB::table('hop_dong_su_dung_dich_vu')
            ->whereIn('id', $totals->keys())
            ->select(['id', 'thong_tin_dieu_phoi'])
            ->get();

        foreach ($rows as $row) {
            $total = max(0, (int) ($totals[$row->id] ?? 0));
            $payload = $this->decodeJson($row->thong_tin_dieu_phoi) ?? [];
            $payload[self::SO_ANH_CHINH_SUA_KEY] = $this->buildSoAnhChinhSuaField($total);

            DB::table('hop_dong_su_dung_dich_vu')
                ->where('id', $row->id)
                ->update(['thong_tin_dieu_phoi' => json_encode($payload, JSON_UNESCAPED_UNICODE)]);
        }
    }

    public function down(): void
    {
        $rows = DB::table('hop_dong_su_dung_dich_vu')
            ->whereNotNull('thong_tin_dieu_phoi')
            ->select(['id', 'thong_tin_dieu_phoi'])
            ->get();

        foreach ($rows as $row) {
            $payload = $this->decodeJson($row->thong_tin_dieu_phoi);
            if ($payload === null || ! array_key_exists(self::SO_ANH_CHINH_SUA_KEY, $payload)) {
                continue;
            }

            unset($payload[self::SO_ANH_CHINH_SUA_KEY]);

            DB::table('hop_dong_su_dung_dich_vu')
                ->where('id', $row->id)
                ->update([
                    'thong_tin_dieu_phoi' => $payload === []
                        ? null
                        : json_encode($payload, JSON_UNESCAPED_UNICODE),
                ]);
        }
    }

    /**
     * @return array{su_dung: bool, ten_thong_tin: string, loai_du_lieu: string, gia_tri: int}
     */
    private function buildSoAnhChinhSuaField(int $total): array
    {
        return [
            'su_dung' => true,
            'ten_thong_tin' => 'Số ảnh chỉnh sửa',
            'loai_du_lieu' => 'number',
            'gia_tri' => $total,
        ];
    }

    /**
     * @return array<int|string, mixed>|null
     */
    private function decodeJson(mixed $value): ?array
    {
        if (is_array($value)) {
            return $value;
        }

        if (! is_string($value) || $value === '') {
            return null;
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : null;
    }
};
