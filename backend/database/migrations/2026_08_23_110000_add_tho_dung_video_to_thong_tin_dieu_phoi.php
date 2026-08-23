<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * @var array<string, array<string, mixed>>
     */
    private const NEW_FIELDS = [
        'tho_dung_video' => [
            'su_dung' => true,
            'ten_thong_tin' => 'Thợ dựng video',
            'loai_du_lieu' => 'array',
            'gia_tri' => [],
            'gia_tri_toi_da' => 1,
        ],
        'tho_dung_video_ngoai' => [
            'su_dung' => true,
            'ten_thong_tin' => 'Thợ dựng video ngoài',
            'loai_du_lieu' => 'string',
            'gia_tri' => null,
        ],
    ];

    public function up(): void
    {
        $this->addFields('danh_muc_loai_hop_dong');
        $this->addFields('hop_dong_su_dung_dich_vu');
    }

    public function down(): void
    {
        $this->removeFields('danh_muc_loai_hop_dong');
        $this->removeFields('hop_dong_su_dung_dich_vu');
    }

    private function addFields(string $table): void
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
            $next = $this->mapSessions($payload, fn (array $map) => $this->insertFields($map));
            if (json_encode($next) === $original) {
                continue;
            }

            DB::table($table)
                ->where('id', $row->id)
                ->update(['thong_tin_dieu_phoi' => json_encode($next, JSON_UNESCAPED_UNICODE)]);
        }
    }

    private function removeFields(string $table): void
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

            $next = $this->mapSessions($payload, function (array $map) {
                foreach (array_keys(self::NEW_FIELDS) as $key) {
                    unset($map[$key]);
                }

                return $map;
            });

            DB::table($table)
                ->where('id', $row->id)
                ->update(['thong_tin_dieu_phoi' => json_encode($next, JSON_UNESCAPED_UNICODE)]);
        }
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

            return $payload;
        }

        if ($payload !== [] && array_is_list($payload)) {
            return array_map(function ($session) use ($callback) {
                return is_array($session) && ! array_is_list($session)
                    ? $callback($session)
                    : $session;
            }, $payload);
        }

        return $callback($payload);
    }

    /**
     * @param  array<string, mixed>  $map
     * @return array<string, mixed>
     */
    private function insertFields(array $map): array
    {
        $missing = [];
        foreach (self::NEW_FIELDS as $key => $field) {
            if (! isset($map[$key]) || ! is_array($map[$key])) {
                $missing[$key] = $field;
            }
        }

        if ($missing === []) {
            return $map;
        }

        $result = [];
        $inserted = false;
        foreach ($map as $key => $value) {
            $result[$key] = $value;
            if ($key === 'quay_phim_ngoai') {
                foreach ($missing as $newKey => $field) {
                    $result[$newKey] = $field;
                }
                $inserted = true;
            }
        }

        if (! $inserted) {
            foreach ($missing as $newKey => $field) {
                $result[$newKey] = $field;
            }
        }

        return $result;
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
