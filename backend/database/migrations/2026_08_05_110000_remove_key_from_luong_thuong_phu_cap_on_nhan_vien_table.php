<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('nhan_vien')->orderBy('id')->select(['id', 'luong_thuong_phu_cap'])->chunkById(100, function ($rows) {
            foreach ($rows as $row) {
                $data = is_string($row->luong_thuong_phu_cap)
                    ? json_decode($row->luong_thuong_phu_cap, true)
                    : (is_array($row->luong_thuong_phu_cap) ? $row->luong_thuong_phu_cap : null);

                if (! is_array($data) || $data === []) {
                    continue;
                }

                $payload = [];
                foreach ($data as $fieldKey => $item) {
                    if (! is_array($item)) {
                        continue;
                    }

                    $payload[$fieldKey] = [
                        'name' => $item['name'] ?? null,
                        'value' => array_key_exists('value', $item) ? $item['value'] : null,
                        'note' => array_key_exists('note', $item) ? $item['note'] : null,
                    ];
                }

                DB::table('nhan_vien')
                    ->where('id', $row->id)
                    ->update([
                        'luong_thuong_phu_cap' => json_encode($payload, JSON_UNESCAPED_UNICODE),
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

                $payload = [];
                foreach ($data as $fieldKey => $item) {
                    if (! is_array($item)) {
                        continue;
                    }

                    $payload[$fieldKey] = [
                        'key' => $fieldKey,
                        'name' => $item['name'] ?? null,
                        'value' => array_key_exists('value', $item) ? $item['value'] : null,
                        'note' => array_key_exists('note', $item) ? $item['note'] : null,
                    ];
                }

                DB::table('nhan_vien')
                    ->where('id', $row->id)
                    ->update([
                        'luong_thuong_phu_cap' => json_encode($payload, JSON_UNESCAPED_UNICODE),
                    ]);
            }
        });
    }
};
