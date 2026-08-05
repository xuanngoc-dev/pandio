<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * @var array<string, array{to: string, name: string, note: string|null}>
     */
    private const RENAMES = [
        'luong_co_ban' => [
            'to' => 'luong_1_gio',
            'name' => 'Lương 1 giờ',
            'note' => 'Dành cho part time',
        ],
        'luong_tang_ca' => [
            'to' => 'luong_tang_ca_1_gio',
            'name' => 'Lương tăng ca 1 giờ',
            'note' => 'Dành cho cả part_time và full_time',
        ],
    ];

    public function up(): void
    {
        $this->applyRenames(self::RENAMES);
    }

    public function down(): void
    {
        $this->applyRenames([
            'luong_1_gio' => [
                'to' => 'luong_co_ban',
                'name' => 'Lương cơ bản',
                'note' => null,
            ],
            'luong_tang_ca_1_gio' => [
                'to' => 'luong_tang_ca',
                'name' => 'Lương tăng ca',
                'note' => null,
            ],
        ]);
    }

    /**
     * @param  array<string, array{to: string, name: string, note: string|null}>  $renames
     */
    private function applyRenames(array $renames): void
    {
        DB::table('nhan_vien')->orderBy('id')->select(['id', 'luong_thuong_phu_cap'])->chunkById(100, function ($rows) use ($renames) {
            foreach ($rows as $row) {
                $data = is_string($row->luong_thuong_phu_cap)
                    ? json_decode($row->luong_thuong_phu_cap, true)
                    : (is_array($row->luong_thuong_phu_cap) ? $row->luong_thuong_phu_cap : null);

                if (! is_array($data)) {
                    continue;
                }

                $changed = false;
                foreach ($renames as $from => $meta) {
                    if (! array_key_exists($from, $data)) {
                        continue;
                    }

                    $item = is_array($data[$from]) ? $data[$from] : [];
                    $data[$meta['to']] = [
                        'name' => $meta['name'],
                        'value' => array_key_exists('value', $item) ? $item['value'] : null,
                        'note' => $meta['note'],
                    ];
                    unset($data[$from]);
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
