<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $this->migrateTable('danh_muc_loai_hop_dong', reverse: false);
        $this->migrateTable('hop_dong_su_dung_dich_vu', reverse: false);
    }

    public function down(): void
    {
        $this->migrateTable('danh_muc_loai_hop_dong', reverse: true);
        $this->migrateTable('hop_dong_su_dung_dich_vu', reverse: true);
    }

    private function migrateTable(string $table, bool $reverse): void
    {
        $rows = DB::table($table)
            ->whereNotNull('thong_tin_dieu_phoi')
            ->select(['id', 'thong_tin_dieu_phoi'])
            ->get();

        foreach ($rows as $row) {
            $payload = $this->decodeJson($row->thong_tin_dieu_phoi);
            if ($payload === null) {
                continue;
            }

            $original = json_encode($payload);
            $next = $this->transformPayload($payload, $reverse);
            if (json_encode($next) === $original) {
                continue;
            }

            DB::table($table)
                ->where('id', $row->id)
                ->update(['thong_tin_dieu_phoi' => json_encode($next, JSON_UNESCAPED_UNICODE)]);
        }
    }

    /**
     * @param  array<int|string, mixed>  $payload
     * @return array<int|string, mixed>
     */
    private function transformPayload(array $payload, bool $reverse): array
    {
        $isEnvelope = isset($payload['danh_sach_buoi_chup']) && is_array($payload['danh_sach_buoi_chup']);

        if ($reverse) {
            if ($isEnvelope) {
                unset($payload['note_tho_shop']);
            } else {
                unset($payload['note_tho_shop']);
            }

            return $payload;
        }

        if ($isEnvelope) {
            if (! array_key_exists('note_tho_shop', $payload) || $payload['note_tho_shop'] === null) {
                $payload['note_tho_shop'] = '';
            }

            return $payload;
        }

        return $this->insertSchemaField(
            $payload,
            'note_tho_shop',
            $this->stringSchemaField('Note thợ shop'),
            'ngay_khach_hen_qua',
        );
    }

    /**
     * @param  array<string, mixed>  $map
     * @param  array<string, mixed>  $field
     * @return array<string, mixed>
     */
    private function insertSchemaField(array $map, string $newKey, array $field, string $afterKey): array
    {
        if (isset($map[$newKey]) && is_array($map[$newKey])) {
            if (array_key_exists('ten_thong_tin', $map[$newKey])) {
                $map[$newKey]['ten_thong_tin'] = $field['ten_thong_tin'];
            }

            return $map;
        }

        $result = [];
        $inserted = false;
        foreach ($map as $key => $value) {
            $result[$key] = $value;
            if ($key === $afterKey) {
                $result[$newKey] = $field;
                $inserted = true;
            }
        }
        if (! $inserted) {
            $result[$newKey] = $field;
        }

        return $result;
    }

    /**
     * @return array{su_dung: bool, ten_thong_tin: string, loai_du_lieu: string, gia_tri: string}
     */
    private function stringSchemaField(string $tenThongTin): array
    {
        return [
            'su_dung' => true,
            'ten_thong_tin' => $tenThongTin,
            'loai_du_lieu' => 'string',
            'gia_tri' => '',
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
