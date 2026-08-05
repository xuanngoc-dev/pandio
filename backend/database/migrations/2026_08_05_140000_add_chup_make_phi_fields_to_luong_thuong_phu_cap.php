<?php

use App\Models\NhanVien;
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
        'chup_1_diem' => 'Chụp 1 điểm',
        'chup_2_diem' => 'Chụp 2 điểm',
        'chup_3_diem' => 'Chụp 3 điểm',
        'make_1_diem' => 'Make 1 điểm',
        'make_2_diem' => 'Make 2 điểm',
        'make_3_diem' => 'Make 3 điểm',
        'phi_xu_ly_hd_thue_trang_phuc' => 'Phí xử lý HĐ thuê trang phục',
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
                        'value' => null,
                        'note' => null,
                    ];
                    $changed = true;
                }

                // Giữ thứ tự theo định nghĩa hiện tại của model (nếu có).
                $ordered = [];
                foreach (NhanVien::salaryFieldDefinitions() as $key => $name) {
                    if (! array_key_exists($key, $data) || ! is_array($data[$key])) {
                        $ordered[$key] = [
                            'name' => $name,
                            'value' => null,
                            'note' => null,
                        ];
                        $changed = true;
                        continue;
                    }

                    $item = $data[$key];
                    $ordered[$key] = [
                        'name' => $item['name'] ?? $name,
                        'value' => array_key_exists('value', $item) ? $item['value'] : null,
                        'note' => array_key_exists('note', $item) ? $item['note'] : null,
                    ];
                }

                if (! $changed && $ordered === $data) {
                    continue;
                }

                DB::table('nhan_vien')
                    ->where('id', $row->id)
                    ->update([
                        'luong_thuong_phu_cap' => json_encode($ordered, JSON_UNESCAPED_UNICODE),
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

                foreach (array_keys(self::NEW_FIELDS) as $key) {
                    unset($data[$key]);
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
