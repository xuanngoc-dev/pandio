<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Các khoản bổ sung vào luong_thuong_phu_cap.
     *
     * @var array<string, string>
     */
    private const NEW_FIELDS = [
        'phu_cap_thu_bay_va_chu_nhat' => 'Phụ cấp thứ 7/chủ nhật',
        'chuyen_can_khong_nghi' => 'Chuyên cần không nghỉ',
        'chuyen_can_nghi_1_ngay' => 'Chuyên cần nghỉ 1 ngày',
        'chuyen_can_nghi_2_ngay' => 'Chuyên cần nghỉ 2 ngày',
        'chuyen_can_nghi_3_ngay' => 'Chuyên cần nghỉ 3 ngày',
    ];

    public function up(): void
    {
        DB::table('nhan_vien')->orderBy('id')->select(['id', 'luong_thuong_phu_cap'])->chunkById(100, function ($rows) {
            foreach ($rows as $row) {
                $data = is_string($row->luong_thuong_phu_cap)
                    ? json_decode($row->luong_thuong_phu_cap, true)
                    : (is_array($row->luong_thuong_phu_cap) ? $row->luong_thuong_phu_cap : []);

                if (! is_array($data)) {
                    $data = [];
                }

                $changed = false;
                foreach (self::NEW_FIELDS as $key => $name) {
                    if (array_key_exists($key, $data) && is_array($data[$key])) {
                        continue;
                    }

                    $data[$key] = [
                        'name' => $name,
                        'value' => 0,
                        'note' => null,
                    ];
                    $changed = true;
                }

                if (! $changed) {
                    continue;
                }

                DB::table('nhan_vien')
                    ->where('id', $row->id)
                    ->update([
                        'luong_thuong_phu_cap' => json_encode($data, JSON_UNESCAPED_UNICODE),
                    ]);
            }
        });
    }

    public function down(): void
    {
        DB::table('nhan_vien')->orderBy('id')->select(['id', 'luong_thuong_phu_cap'])->chunkById(100, function ($rows) {
            foreach ($rows as $row) {
                $data = is_string($row->luong_thuong_phu_cap)
                    ? json_decode($row->luong_thuong_phu_cap, true)
                    : (is_array($row->luong_thuong_phu_cap) ? $row->luong_thuong_phu_cap : null);

                if (! is_array($data) || $data === []) {
                    continue;
                }

                $changed = false;
                foreach (array_keys(self::NEW_FIELDS) as $key) {
                    if (! array_key_exists($key, $data)) {
                        continue;
                    }
                    unset($data[$key]);
                    $changed = true;
                }

                if (! $changed) {
                    continue;
                }

                DB::table('nhan_vien')
                    ->where('id', $row->id)
                    ->update([
                        'luong_thuong_phu_cap' => json_encode($data, JSON_UNESCAPED_UNICODE),
                    ]);
            }
        });
    }
};
