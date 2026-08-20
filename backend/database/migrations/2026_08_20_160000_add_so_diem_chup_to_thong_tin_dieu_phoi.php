<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $this->addSoDiemChup('danh_muc_loai_hop_dong');
        $this->addSoDiemChup('hop_dong_su_dung_dich_vu');
    }

    public function down(): void
    {
        $this->removeSoDiemChup('danh_muc_loai_hop_dong');
        $this->removeSoDiemChup('hop_dong_su_dung_dich_vu');
    }

    private function addSoDiemChup(string $table): void
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

            $next = $this->mapSessions($payload, fn (array $map) => $this->insertSoDiemChup($map));
            if ($next === $payload) {
                continue;
            }

            DB::table($table)
                ->where('id', $row->id)
                ->update(['thong_tin_dieu_phoi' => json_encode($next, JSON_UNESCAPED_UNICODE)]);
        }
    }

    private function removeSoDiemChup(string $table): void
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
                unset($map['so_diem_chup']);

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
    private function insertSoDiemChup(array $map): array
    {
        if (isset($map['so_diem_chup']) && is_array($map['so_diem_chup'])) {
            return $map;
        }

        $field = [
            'su_dung' => true,
            'ten_thong_tin' => 'Số điểm chụp',
            'loai_du_lieu' => 'number',
            'gia_tri' => 1,
        ];

        $result = [];
        $inserted = false;

        foreach ($map as $key => $value) {
            $result[$key] = $value;
            if ($key === 'ngay_chup') {
                $result['so_diem_chup'] = $field;
                $inserted = true;
            }
        }

        if (! $inserted) {
            $result['so_diem_chup'] = $field;
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
