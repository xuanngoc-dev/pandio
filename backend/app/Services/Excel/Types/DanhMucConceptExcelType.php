<?php

namespace App\Services\Excel\Types;

use App\Models\DanhMucConcept;
use App\Services\Excel\BaseExcelType;
use Throwable;

class DanhMucConceptExcelType extends BaseExcelType
{
    public function key(): string
    {
        return 'danh_muc_concept';
    }

    public function label(): string
    {
        return 'Danh mục concept';
    }

    public function filename(): string
    {
        return 'danh-muc-concept';
    }

    public function columns(): array
    {
        return [
            'ten_danh_muc' => [
                'header' => 'Tên danh mục',
                'aliases' => ['ten', 'ten danh muc', 'tên', 'tên dm'],
                'required' => true,
                'max' => 255,
            ],
            'mo_ta' => [
                'header' => 'Mô tả',
                'aliases' => ['mo ta', 'ghi chu', 'ghi chú'],
                'required' => false,
            ],
        ];
    }

    public function exportRows(): array
    {
        return DanhMucConcept::query()
            ->orderBy('id')
            ->get(['ten_danh_muc', 'mo_ta'])
            ->map(fn (DanhMucConcept $row) => [
                'ten_danh_muc' => $row->ten_danh_muc,
                'mo_ta' => $row->mo_ta,
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

            $loi = $this->validateValues($values);
            if ($loi !== null) {
                $thatBai[] = $this->failItem($hang, $loi, $values);
                continue;
            }

            $hopLe[] = [
                'hang' => $hang,
                'values' => $this->normalizeValues($values),
            ];
        }

        $tenTrongFile = array_values(array_unique(array_map(
            fn (array $item) => $item['values']['ten_danh_muc'],
            $hopLe
        )));

        $tenDaCo = $tenTrongFile === []
            ? []
            : DanhMucConcept::query()
                ->whereIn('ten_danh_muc', $tenTrongFile)
                ->pluck('ten_danh_muc')
                ->all();
        $tenDaCo = array_fill_keys($tenDaCo, true);
        $tenDaXem = [];

        $canThem = [];
        foreach ($hopLe as $item) {
            $hang = $item['hang'];
            $values = $item['values'];
            $ten = $values['ten_danh_muc'];

            if (isset($tenDaCo[$ten])) {
                $thatBai[] = $this->failItem($hang, 'Tên danh mục đã tồn tại.', $values);
                continue;
            }

            if (isset($tenDaXem[$ten])) {
                $thatBai[] = $this->failItem(
                    $hang,
                    "Tên danh mục trùng với dòng {$tenDaXem[$ten]} trong file.",
                    $values
                );
                continue;
            }

            $tenDaXem[$ten] = $hang;
            $canThem[] = $item;
        }

        foreach ($canThem as $item) {
            try {
                $created = DanhMucConcept::query()->create($item['values']);
                $thanhCong[] = [
                    'hang' => $item['hang'],
                    'id' => $created->id,
                    'ten_danh_muc' => $created->ten_danh_muc,
                    'mo_ta' => $created->mo_ta,
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
     */
    private function validateValues(array $values): ?string
    {
        $tenDanhMuc = trim((string) ($values['ten_danh_muc'] ?? ''));

        if ($tenDanhMuc === '') {
            return 'Thiếu tên danh mục.';
        }

        if (mb_strlen($tenDanhMuc) > 255) {
            return 'Tên danh mục tối đa 255 ký tự.';
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    private function normalizeValues(array $values): array
    {
        $moTa = trim((string) ($values['mo_ta'] ?? ''));

        return [
            'ten_danh_muc' => trim((string) ($values['ten_danh_muc'] ?? '')),
            'mo_ta' => $moTa === '' ? null : $moTa,
        ];
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
