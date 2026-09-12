<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const THOI_GIAN_UP_FILE_GOC_KEY = 'thoi_gian_up_file_goc';

    public function up(): void
    {
        $rows = DB::table('hop_dong_su_dung_dich_vu')
            ->select(['id', 'thong_tin_dieu_phoi', 'ket_qua_hop_dong'])
            ->get();

        foreach ($rows as $row) {
            $payload = $this->decodeJson($row->thong_tin_dieu_phoi) ?? [];
            $ketQua = $this->decodeJson($row->ket_qua_hop_dong) ?? [];
            $original = json_encode($payload);

            $current = $payload[self::THOI_GIAN_UP_FILE_GOC_KEY] ?? null;
            if ($current === null || $current === '') {
                $fallback = $ketQua['link_file_goc']['thoi_gian_up_file'] ?? null;
                $payload[self::THOI_GIAN_UP_FILE_GOC_KEY] =
                    ($fallback !== null && $fallback !== '')
                        ? (is_string($fallback) ? $fallback : (string) $fallback)
                        : null;
            }

            if (json_encode($payload) === $original) {
                continue;
            }

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
            if ($payload === null || ! array_key_exists(self::THOI_GIAN_UP_FILE_GOC_KEY, $payload)) {
                continue;
            }

            unset($payload[self::THOI_GIAN_UP_FILE_GOC_KEY]);

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
