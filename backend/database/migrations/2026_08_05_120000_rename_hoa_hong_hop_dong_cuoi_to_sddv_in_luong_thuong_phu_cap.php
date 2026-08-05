<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const OLD_KEY = 'hoa_hong_hop_dong_cuoi';

    private const NEW_KEY = 'hoa_hong_hop_dong_sddv';

    private const NEW_NAME = 'Hoa hồng HĐ sử dụng dịch vụ';

    public function up(): void
    {
        $this->renameSalaryKey(self::OLD_KEY, self::NEW_KEY, self::NEW_NAME);
    }

    public function down(): void
    {
        $this->renameSalaryKey(self::NEW_KEY, self::OLD_KEY, 'Hoa hồng HĐ cuối');
    }

    private function renameSalaryKey(string $from, string $to, string $name): void
    {
        DB::table('nhan_vien')->orderBy('id')->select(['id', 'luong_thuong_phu_cap'])->chunkById(100, function ($rows) use ($from, $to, $name) {
            foreach ($rows as $row) {
                $data = is_string($row->luong_thuong_phu_cap)
                    ? json_decode($row->luong_thuong_phu_cap, true)
                    : (is_array($row->luong_thuong_phu_cap) ? $row->luong_thuong_phu_cap : null);

                if (! is_array($data) || ! array_key_exists($from, $data)) {
                    continue;
                }

                $item = is_array($data[$from]) ? $data[$from] : [];
                $data[$to] = [
                    'name' => $name,
                    'value' => array_key_exists('value', $item) ? $item['value'] : null,
                    'note' => array_key_exists('note', $item) ? $item['note'] : null,
                ];
                unset($data[$from]);

                DB::table('nhan_vien')
                    ->where('id', $row->id)
                    ->update([
                        'luong_thuong_phu_cap' => json_encode($data, JSON_UNESCAPED_UNICODE),
                    ]);
            }
        });
    }
};
