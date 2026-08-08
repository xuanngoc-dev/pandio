<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $this->convertDiaDiemChup('danh_muc_loai_hop_dong');
        $this->convertDiaDiemChup('hop_dong_su_dung_dich_vu');
    }

    public function down(): void
    {
        $this->revertDiaDiemChup('danh_muc_loai_hop_dong');
        $this->revertDiaDiemChup('hop_dong_su_dung_dich_vu');
    }

    private function convertDiaDiemChup(string $table): void
    {
        $rows = DB::table($table)
            ->whereNotNull('thong_tin_dieu_phoi')
            ->select(['id', 'thong_tin_dieu_phoi'])
            ->get();

        foreach ($rows as $row) {
            $payload = $this->decodeJson($row->thong_tin_dieu_phoi);
            if ($payload === null || ! isset($payload['dia_diem_chup']) || ! is_array($payload['dia_diem_chup'])) {
                continue;
            }

            $field = $payload['dia_diem_chup'];
            $giaTri = $field['gia_tri'] ?? null;
            if (is_array($giaTri)) {
                $giaTri = implode(', ', array_values(array_filter(
                    array_map(static fn ($item) => trim((string) $item), $giaTri),
                    static fn ($item) => $item !== ''
                )));
                $giaTri = $giaTri === '' ? null : $giaTri;
            } elseif ($giaTri === '') {
                $giaTri = null;
            }

            $payload['dia_diem_chup'] = [
                'su_dung' => array_key_exists('su_dung', $field) ? (bool) $field['su_dung'] : true,
                'ten_thong_tin' => $field['ten_thong_tin'] ?? 'Địa điểm chụp',
                'loai_du_lieu' => 'string',
                'gia_tri' => $giaTri,
            ];

            DB::table($table)
                ->where('id', $row->id)
                ->update(['thong_tin_dieu_phoi' => json_encode($payload, JSON_UNESCAPED_UNICODE)]);
        }
    }

    private function revertDiaDiemChup(string $table): void
    {
        $rows = DB::table($table)
            ->whereNotNull('thong_tin_dieu_phoi')
            ->select(['id', 'thong_tin_dieu_phoi'])
            ->get();

        foreach ($rows as $row) {
            $payload = $this->decodeJson($row->thong_tin_dieu_phoi);
            if ($payload === null || ! isset($payload['dia_diem_chup']) || ! is_array($payload['dia_diem_chup'])) {
                continue;
            }

            $field = $payload['dia_diem_chup'];
            $giaTri = $field['gia_tri'] ?? null;
            if (is_string($giaTri) && trim($giaTri) !== '') {
                $giaTri = [trim($giaTri)];
            } elseif (! is_array($giaTri)) {
                $giaTri = [];
            }

            $payload['dia_diem_chup'] = [
                'su_dung' => array_key_exists('su_dung', $field) ? (bool) $field['su_dung'] : true,
                'ten_thong_tin' => $field['ten_thong_tin'] ?? 'Địa điểm chụp',
                'loai_du_lieu' => 'array',
                'gia_tri' => $giaTri,
            ];

            DB::table($table)
                ->where('id', $row->id)
                ->update(['thong_tin_dieu_phoi' => json_encode($payload, JSON_UNESCAPED_UNICODE)]);
        }
    }

    /**
     * @return array<string, mixed>|null
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
