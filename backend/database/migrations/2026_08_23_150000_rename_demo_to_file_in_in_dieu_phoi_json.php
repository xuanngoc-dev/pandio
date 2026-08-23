<?php

use App\Models\HopDongSuDungDichVu;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $this->renameDieuPhoiKeys('danh_muc_loai_hop_dong');
        $this->renameDieuPhoiKeys('hop_dong_su_dung_dich_vu');
        $this->migrateKetQuaHopDong();
    }

    public function down(): void
    {
        $this->renameDieuPhoiKeys('danh_muc_loai_hop_dong', reverse: true);
        $this->renameDieuPhoiKeys('hop_dong_su_dung_dich_vu', reverse: true);
        $this->migrateKetQuaHopDong(reverse: true);
    }

    private function renameDieuPhoiKeys(string $table, bool $reverse = false): void
    {
        $from = $reverse ? 'ngay_tra_file_in' : 'ngay_tra_demo';
        $to = $reverse ? 'ngay_tra_demo' : 'ngay_tra_file_in';
        $tenThongTin = $reverse ? 'Ngày trả demo' : 'Ngày trả file in';

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
            $next = $this->renameKeyInMap($payload, $from, $to, $tenThongTin);
            $next = $this->mapSessions($next, fn (array $map) => $this->renameKeyInMap($map, $from, $to, $tenThongTin));
            if (json_encode($next) === $original) {
                continue;
            }

            DB::table($table)
                ->where('id', $row->id)
                ->update(['thong_tin_dieu_phoi' => json_encode($next, JSON_UNESCAPED_UNICODE)]);
        }
    }

    private function migrateKetQuaHopDong(bool $reverse = false): void
    {
        $from = $reverse ? 'link_file_in' : 'link_file_demo';
        $to = $reverse ? 'link_file_demo' : 'link_file_in';
        $ten = $reverse ? 'Link file demo' : 'Link file in';
        $defaults = HopDongSuDungDichVu::defaultKetQuaHopDong();

        $rows = DB::table('hop_dong_su_dung_dich_vu')
            ->whereNotNull('ket_qua_hop_dong')
            ->select(['id', 'ket_qua_hop_dong'])
            ->get();

        foreach ($rows as $row) {
            $ketQua = $this->decodeJson($row->ket_qua_hop_dong);
            if ($ketQua === null) {
                continue;
            }

            $original = json_encode($ketQua);

            if (array_key_exists($from, $ketQua)) {
                $old = $ketQua[$from];
                unset($ketQua[$from]);
                if (! isset($ketQua[$to]) || ! is_array($ketQua[$to])) {
                    $ketQua[$to] = is_array($old) ? $old : [];
                } elseif (is_array($old)) {
                    $newVal = trim((string) ($ketQua[$to]['gia_tri'] ?? ''));
                    $oldVal = trim((string) ($old['gia_tri'] ?? ''));
                    if ($newVal === '' && $oldVal !== '') {
                        $ketQua[$to] = array_merge($ketQua[$to], $old);
                    }
                }
            }

            if (isset($ketQua[$to]) && is_array($ketQua[$to])) {
                $ketQua[$to]['ten'] = $ten;
                if (! $reverse && ! array_key_exists('thoi_gian_up_file', $ketQua[$to])) {
                    $ketQua[$to]['thoi_gian_up_file'] = null;
                }
                if ($reverse) {
                    unset($ketQua[$to]['thoi_gian_up_file']);
                }
            }

            if (! $reverse) {
                if (! isset($ketQua['link_file_le']) || ! is_array($ketQua['link_file_le'])) {
                    $ketQua['link_file_le'] = $defaults['link_file_le'];
                } elseif (! array_key_exists('thoi_gian_up_file', $ketQua['link_file_le'])) {
                    $ketQua['link_file_le']['thoi_gian_up_file'] = null;
                }
            } else {
                unset($ketQua['link_file_le']);
            }

            if (json_encode($ketQua) === $original) {
                continue;
            }

            DB::table('hop_dong_su_dung_dich_vu')
                ->where('id', $row->id)
                ->update(['ket_qua_hop_dong' => json_encode($ketQua, JSON_UNESCAPED_UNICODE)]);
        }
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

        if (is_array($value) && ! array_is_list($value)) {
            if (array_key_exists('ten_thong_tin', $value)) {
                $value['ten_thong_tin'] = $tenThongTin;
            }
        }

        if (! array_key_exists($to, $map)) {
            $map[$to] = $value;
        }

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
