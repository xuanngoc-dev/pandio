<?php

namespace App\Services\Excel\Types;

use App\Models\DichVuLoaiDichVu;
use App\Services\Excel\BaseExcelType;
use Throwable;

class LoaiDichVuExcelType extends BaseExcelType
{
    /** @var array<string, string> */
    private const TRANG_THAI_LABELS = [
        'dang_hoat_dong' => 'Đang hoạt động',
        'ngung_hoat_dong' => 'Ngừng hoạt động',
    ];

    public function key(): string
    {
        return 'loai_dich_vu';
    }

    public function label(): string
    {
        return 'Loại dịch vụ';
    }

    public function filename(): string
    {
        return 'loai-dich-vu';
    }

    public function columns(): array
    {
        return [
            'ten_dich_vu' => [
                'header' => 'Tên loại dịch vụ',
                'aliases' => ['ten', 'ten dich vu', 'tên', 'tên loại dịch vụ'],
                'required' => true,
                'max' => 255,
            ],
            'mo_ta' => [
                'header' => 'Mô tả',
                'aliases' => ['mo ta', 'ghi chu', 'ghi chú'],
                'required' => false,
            ],
            'trang_thai' => [
                'header' => 'Trạng thái',
                'aliases' => ['trang thai', 'ma trang thai'],
                'required' => false,
            ],
        ];
    }

    public function extraSheets(): array
    {
        return [
            [
                'title' => 'Trạng thái',
                'headers' => [
                    'trang_thai' => 'Mã trạng thái',
                    'ten_trang_thai' => 'Tên trạng thái',
                ],
                'rows' => collect(self::TRANG_THAI_LABELS)
                    ->map(fn (string $ten, string $ma) => [
                        'trang_thai' => $ma,
                        'ten_trang_thai' => $ten,
                    ])
                    ->values()
                    ->all(),
            ],
        ];
    }

    public function exportRows(): array
    {
        return DichVuLoaiDichVu::query()
            ->orderBy('id')
            ->get(['ten_dich_vu', 'mo_ta', 'trang_thai'])
            ->map(fn (DichVuLoaiDichVu $row) => [
                'ten_dich_vu' => $row->ten_dich_vu,
                'mo_ta' => $row->mo_ta,
                'trang_thai' => $row->trang_thai,
            ])
            ->all();
    }

    public function importItems(array $items): array
    {
        $thanhCong = [];
        $thatBai = [];
        $hopLe = [];

        foreach ($items as $item) {
            $hang = (int) ($item['hang'] ?? $item['row'] ?? 0);
            $values = $this->itemValues($item);

            if ($this->isEmptyRow($values)) {
                continue;
            }

            $parsed = $this->parseRow($values);
            if ($parsed['loi'] !== null) {
                $thatBai[] = $this->failItem($hang, $parsed['loi'], $values);
                continue;
            }

            $hopLe[] = [
                'hang' => $hang,
                'values' => $values,
                'payload' => $parsed['payload'],
            ];
        }

        $tenTrongFile = array_values(array_unique(array_map(
            fn (array $item) => $item['payload']['ten_dich_vu'],
            $hopLe
        )));

        $tenDaCo = $tenTrongFile === []
            ? []
            : DichVuLoaiDichVu::query()
                ->whereIn('ten_dich_vu', $tenTrongFile)
                ->pluck('ten_dich_vu')
                ->all();
        $tenDaCo = array_fill_keys($tenDaCo, true);
        $tenDaXem = [];

        $canThem = [];
        foreach ($hopLe as $item) {
            $hang = $item['hang'];
            $values = $item['values'];
            $ten = $item['payload']['ten_dich_vu'];

            if (isset($tenDaCo[$ten])) {
                $thatBai[] = $this->failItem($hang, 'Tên loại dịch vụ đã tồn tại.', $values);
                continue;
            }

            if (isset($tenDaXem[$ten])) {
                $thatBai[] = $this->failItem(
                    $hang,
                    "Tên loại dịch vụ trùng với dòng {$tenDaXem[$ten]} trong file.",
                    $values
                );
                continue;
            }

            $tenDaXem[$ten] = $hang;
            $canThem[] = $item;
        }

        foreach ($canThem as $item) {
            try {
                $created = DichVuLoaiDichVu::query()->create($item['payload']);
                $thanhCong[] = [
                    'hang' => $item['hang'],
                    'id' => $created->id,
                    'ten_dich_vu' => $created->ten_dich_vu,
                    'mo_ta' => $created->mo_ta,
                    'trang_thai' => $created->trang_thai,
                ];
            } catch (Throwable) {
                $thatBai[] = $this->failItem(
                    $item['hang'],
                    'Không thể lưu dòng này. Vui lòng kiểm tra lại dữ liệu.',
                    $item['values']
                );
            }
        }

        return [
            'thanh_cong' => $thanhCong,
            'that_bai' => $thatBai,
        ];
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array{loi: ?string, payload: array<string, mixed>}
     */
    private function parseRow(array $values): array
    {
        $ten = trim((string) ($values['ten_dich_vu'] ?? ''));

        if ($ten === '') {
            return $this->parseFail('Thiếu tên loại dịch vụ.');
        }

        if (mb_strlen($ten) > 255) {
            return $this->parseFail('Tên loại dịch vụ tối đa 255 ký tự.');
        }

        $trangThai = $this->parseTrangThai((string) ($values['trang_thai'] ?? ''));
        if ($trangThai === null) {
            return $this->parseFail('Trạng thái không hợp lệ. Dùng dang_hoat_dong (Đang hoạt động) hoặc ngung_hoat_dong (Ngừng hoạt động).');
        }

        $moTa = trim((string) ($values['mo_ta'] ?? ''));

        return [
            'loi' => null,
            'payload' => [
                'ten_dich_vu' => $ten,
                'mo_ta' => $moTa === '' ? null : $moTa,
                'trang_thai' => $trangThai,
            ],
        ];
    }

    /**
     * @return array{loi: string, payload: array<string, mixed>}
     */
    private function parseFail(string $loi): array
    {
        return ['loi' => $loi, 'payload' => []];
    }

    private function parseTrangThai(string $value): ?string
    {
        $normalized = $this->normalizeLookup($value);
        if ($normalized === '') {
            return 'dang_hoat_dong';
        }

        foreach (self::TRANG_THAI_LABELS as $ma => $ten) {
            if ($normalized === $this->normalizeLookup($ma) || $normalized === $this->normalizeLookup($ten)) {
                return $ma;
            }
        }

        return null;
    }

    private function normalizeLookup(string $value): string
    {
        $value = trim(mb_strtolower($value));

        return preg_replace('/\s+/u', ' ', $value) ?? $value;
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array{hang: int, mo_ta: string, du_lieu: array<string, mixed>}
     */
    private function failItem(int $hang, string $moTa, array $values): array
    {
        return [
            'hang' => $hang,
            'mo_ta' => $moTa,
            'du_lieu' => $values,
        ];
    }
}
