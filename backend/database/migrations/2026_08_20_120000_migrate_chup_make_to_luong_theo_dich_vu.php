<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * @var list<string>
     */
    private const OLD_KEYS = [
        'chup_1_diem',
        'chup_2_diem',
        'chup_3_diem',
        'make_1_diem',
        'make_2_diem',
        'make_3_diem',
    ];

    public function up(): void
    {
        $catalog = DB::table('danh_muc_loai_quay_chup')
            ->orderBy('id')
            ->get(['id', 'ten_dich_vu']);

        DB::table('nhan_vien')->orderBy('id')->select(['id', 'luong_thuong_phu_cap'])->chunkById(100, function ($rows) use ($catalog) {
            foreach ($rows as $row) {
                $data = $this->decode($row->luong_thuong_phu_cap);
                if ($data === null) {
                    continue;
                }

                $existingItems = $data['luong_theo_dich_vu']['items'] ?? null;
                $hasNew = is_array($existingItems) && $existingItems !== [];
                $hasOld = false;
                foreach (self::OLD_KEYS as $key) {
                    if (array_key_exists($key, $data)) {
                        $hasOld = true;
                        break;
                    }
                }

                if (! $hasNew) {
                    $items = [];
                    foreach ($catalog as $loai) {
                        $items[] = [
                            'id' => (int) $loai->id,
                            'ten_dich_vu' => $loai->ten_dich_vu,
                            'chup' => [
                                1 => $this->oldAmount($data, 'chup_1_diem', (int) $loai->id),
                                2 => $this->oldAmount($data, 'chup_2_diem', (int) $loai->id),
                                3 => $this->oldAmount($data, 'chup_3_diem', (int) $loai->id),
                            ],
                            'make' => [
                                1 => $this->oldAmount($data, 'make_1_diem', (int) $loai->id),
                                2 => $this->oldAmount($data, 'make_2_diem', (int) $loai->id),
                                3 => $this->oldAmount($data, 'make_3_diem', (int) $loai->id),
                            ],
                            'quay_phim' => [
                                1 => null,
                                2 => null,
                                3 => null,
                            ],
                        ];
                    }

                    $data['luong_theo_dich_vu'] = [
                        'name' => 'Lương theo dịch vụ',
                        'items' => $items,
                    ];
                }

                if ($hasOld) {
                    foreach (self::OLD_KEYS as $key) {
                        unset($data[$key]);
                    }
                }

                if (! $hasNew && ! $hasOld && $catalog->isEmpty()) {
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
                $data = $this->decode($row->luong_thuong_phu_cap);
                if ($data === null || ! isset($data['luong_theo_dich_vu'])) {
                    continue;
                }

                $items = $data['luong_theo_dich_vu']['items'] ?? [];
                if (! is_array($items)) {
                    $items = [];
                }
                if ($items !== [] && ! array_is_list($items)) {
                    $items = array_values($items);
                }

                foreach (['chup' => 'Chụp', 'make' => 'Make'] as $role => $label) {
                    for ($level = 1; $level <= 3; $level++) {
                        $key = "{$role}_{$level}_diem";
                        $byLoai = [];
                        $first = null;
                        foreach ($items as $item) {
                            if (! is_array($item)) {
                                continue;
                            }
                            $id = $item['id'] ?? null;
                            $amount = $item[$role][$level] ?? $item[$role][(string) $level] ?? null;
                            if ($id !== null && $amount !== null && $amount !== '') {
                                $byLoai[(string) $id] = (float) $amount;
                                $first ??= (float) $amount;
                            }
                        }
                        $data[$key] = [
                            'name' => "{$label} {$level} điểm",
                            'value' => $first,
                            'note' => null,
                            'by_loai' => $byLoai,
                        ];
                    }
                }

                unset($data['luong_theo_dich_vu']);

                DB::table('nhan_vien')
                    ->where('id', $row->id)
                    ->update([
                        'luong_thuong_phu_cap' => json_encode($data, JSON_UNESCAPED_UNICODE),
                    ]);
            }
        });
    }

    /**
     * @return array<string, mixed>|null
     */
    private function decode(mixed $raw): ?array
    {
        $data = is_string($raw) ? json_decode($raw, true) : $raw;

        return is_array($data) ? $data : null;
    }

    private function oldAmount(array $data, string $key, int $loaiId): ?float
    {
        $item = $data[$key] ?? [];
        if (! is_array($item)) {
            return null;
        }

        $byLoai = $item['by_loai'] ?? [];
        if (is_array($byLoai)) {
            $specific = $byLoai[$loaiId] ?? $byLoai[(string) $loaiId] ?? null;
            if ($specific !== null && $specific !== '') {
                return (float) $specific;
            }
        }

        $value = $item['value'] ?? null;

        return $value !== null && $value !== '' ? (float) $value : null;
    }
};
