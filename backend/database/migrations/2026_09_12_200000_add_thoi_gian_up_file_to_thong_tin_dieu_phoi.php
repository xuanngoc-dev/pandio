<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const THOI_GIAN_UP_FILE_LE_KEY = 'thoi_gian_up_file_le';

    private const THOI_GIAN_UP_FILE_IN_KEY = 'thoi_gian_up_file_in';

    public function up(): void
    {
        $rows = DB::table('hop_dong_su_dung_dich_vu')
            ->select(['id', 'thong_tin_dieu_phoi', 'ket_qua_hop_dong'])
            ->get();

        foreach ($rows as $row) {
            $payload = $this->decodeJson($row->thong_tin_dieu_phoi) ?? [];
            $ketQua = $this->decodeJson($row->ket_qua_hop_dong) ?? [];
            $original = json_encode($payload);

            $payload = $this->stampKey(
                $payload,
                self::THOI_GIAN_UP_FILE_LE_KEY,
                $this->ketQuaUploadTime($ketQua, 'link_file_le'),
            );
            $payload = $this->stampKey(
                $payload,
                self::THOI_GIAN_UP_FILE_IN_KEY,
                $this->ketQuaUploadTime($ketQua, 'link_file_in'),
            );

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
            if ($payload === null) {
                continue;
            }

            $changed = false;
            foreach ([self::THOI_GIAN_UP_FILE_LE_KEY, self::THOI_GIAN_UP_FILE_IN_KEY] as $key) {
                if (! array_key_exists($key, $payload)) {
                    continue;
                }
                unset($payload[$key]);
                $changed = true;
            }

            if (! $changed) {
                continue;
            }

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
     * @param  array<int|string, mixed>  $payload
     * @return array<int|string, mixed>
     */
    private function stampKey(array $payload, string $key, ?string $fallback): array
    {
        $current = $payload[$key] ?? null;
        if ($current !== null && $current !== '') {
            return $payload;
        }

        $payload[$key] = ($fallback !== null && $fallback !== '') ? $fallback : null;

        return $payload;
    }

    /**
     * @param  array<int|string, mixed>  $ketQua
     */
    private function ketQuaUploadTime(array $ketQua, string $field): ?string
    {
        $value = $ketQua[$field]['thoi_gian_up_file'] ?? null;
        if ($value === null || $value === '') {
            return null;
        }

        return is_string($value) ? $value : (string) $value;
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
