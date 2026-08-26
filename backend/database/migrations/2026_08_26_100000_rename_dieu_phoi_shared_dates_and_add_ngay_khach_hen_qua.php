<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * ngay_tra_file_in → ngay_tra_file_le (Ngày trả file lẻ)
     * ngay_tra_chinh_thuc → ngay_tra_file_in (Ngày trả file in)
     * thêm ngay_khach_hen_qua (Ngày khách hẹn qua)
     */
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
                unset($payload['ngay_khach_hen_qua']);
            } else {
                $payload = $this->removeKey($payload, 'ngay_khach_hen_qua');
            }
            $payload = $this->renameKeyInMap($payload, 'ngay_tra_file_in', 'ngay_tra_chinh_thuc', 'Ngày trả chính thức');
            $payload = $this->renameKeyInMap($payload, 'ngay_tra_file_le', 'ngay_tra_file_in', 'Ngày trả file in');
            $payload = $this->mapSessions($payload, function (array $map) {
                $map = $this->removeKey($map, 'ngay_khach_hen_qua');
                $map = $this->renameKeyInMap($map, 'ngay_tra_file_in', 'ngay_tra_chinh_thuc', 'Ngày trả chính thức');

                return $this->renameKeyInMap($map, 'ngay_tra_file_le', 'ngay_tra_file_in', 'Ngày trả file in');
            });

            return $payload;
        }

        $payload = $this->migrateSharedDateKeys($payload);
        $payload = $this->mapSessions($payload, fn (array $map) => $this->migrateSharedDateKeys($map));

        if ($isEnvelope) {
            if (! array_key_exists('ngay_khach_hen_qua', $payload) || $payload['ngay_khach_hen_qua'] === null) {
                $payload['ngay_khach_hen_qua'] = '';
            }
        } else {
            $payload = $this->insertSchemaField(
                $payload,
                'ngay_khach_hen_qua',
                $this->dateSchemaField('Ngày khách hẹn qua'),
                'ngay_tra_file_in',
            );
        }

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $map
     * @return array<string, mixed>
     */
    private function migrateSharedDateKeys(array $map): array
    {
        if (! array_key_exists('ngay_tra_file_le', $map)) {
            $map = $this->renameKeyInMap($map, 'ngay_tra_file_in', 'ngay_tra_file_le', 'Ngày trả file lẻ');
        }

        return $this->renameKeyInMap($map, 'ngay_tra_chinh_thuc', 'ngay_tra_file_in', 'Ngày trả file in');
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
     * @return array{su_dung: bool, ten_thong_tin: string, loai_du_lieu: string, gia_tri: null}
     */
    private function dateSchemaField(string $tenThongTin): array
    {
        return [
            'su_dung' => true,
            'ten_thong_tin' => $tenThongTin,
            'loai_du_lieu' => 'date',
            'gia_tri' => null,
        ];
    }

    /**
     * @param  array<string, mixed>  $map
     * @return array<string, mixed>
     */
    private function renameKeyInMap(array $map, string $from, string $to, string $tenThongTin): array
    {
        if (! array_key_exists($from, $map)) {
            return $map;
        }

        $value = $map[$from];
        unset($map[$from]);

        if (is_array($value) && ! array_is_list($value) && array_key_exists('ten_thong_tin', $value)) {
            $value['ten_thong_tin'] = $tenThongTin;
        }

        if (! array_key_exists($to, $map)) {
            $map[$to] = $value;
        }

        return $map;
    }

    /**
     * @param  array<string, mixed>  $map
     * @return array<string, mixed>
     */
    private function removeKey(array $map, string $key): array
    {
        unset($map[$key]);

        return $map;
    }

    /**
     * @param  array<int|string, mixed>  $payload
     * @param  callable(array<string, mixed>): array<string, mixed>  $callback
     * @return array<int|string, mixed>
     */
    private function mapSessions(array $payload, callable $callback): array
    {
        if (isset($payload['danh_sach_buoi_chup']) && is_array($payload['danh_sach_buoi_chup'])) {
            $payload['danh_sach_buoi_chup'] = array_map(function ($session) use ($callback) {
                return is_array($session) && ! array_is_list($session)
                    ? $callback($session)
                    : $session;
            }, $payload['danh_sach_buoi_chup']);
        }

        return $payload;
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
