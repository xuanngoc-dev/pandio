<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const KEY = 'hoa_hong_hop_dong_sddv';

    private const NEW_NAME = 'Hoa hồng HĐ sử dụng dịch vụ';

    private const OLD_NAME = 'Hoa hồng hợp đồng sử dụng dịch vụ';

    public function up(): void
    {
        $this->updateName(self::OLD_NAME, self::NEW_NAME);
    }

    public function down(): void
    {
        $this->updateName(self::NEW_NAME, self::OLD_NAME);
    }

    private function updateName(string $from, string $to): void
    {
        DB::table('nhan_vien')->orderBy('id')->select(['id', 'luong_thuong_phu_cap'])->chunkById(100, function ($rows) use ($from, $to) {
            foreach ($rows as $row) {
                $data = is_string($row->luong_thuong_phu_cap)
                    ? json_decode($row->luong_thuong_phu_cap, true)
                    : (is_array($row->luong_thuong_phu_cap) ? $row->luong_thuong_phu_cap : null);

                if (! is_array($data) || ! isset($data[self::KEY]) || ! is_array($data[self::KEY])) {
                    continue;
                }

                if (($data[self::KEY]['name'] ?? null) !== $from) {
                    continue;
                }

                $data[self::KEY]['name'] = $to;

                DB::table('nhan_vien')
                    ->where('id', $row->id)
                    ->update([
                        'luong_thuong_phu_cap' => json_encode($data, JSON_UNESCAPED_UNICODE),
                    ]);
            }
        });
    }
};
