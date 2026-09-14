<?php

namespace App\Services\Excel\Types;

use App\Models\DanhMucTrangPhuc;
use App\Services\Excel\BaseExcelType;
use Throwable;

class DanhMucTrangPhucExcelType extends BaseExcelType
{
    public function key(): string
    {
        return 'danh_muc_trang_phuc';
    }

    public function label(): string
    {
        return 'Danh mục trang phục';
    }

    public function filename(): string
    {
        return 'danh-muc-trang-phuc';
    }

    public function columns(): array
    {
        return [
            'ma_danh_muc' => [
                'header' => 'Mã danh mục',
                'aliases' => ['ma', 'ma danh muc', 'mã', 'mã dm'],
                'required' => true,
                'max' => 50,
            ],
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
        return DanhMucTrangPhuc::query()
            ->orderBy('id')
            ->get(['ma_danh_muc', 'ten_danh_muc', 'mo_ta'])
            ->map(fn (DanhMucTrangPhuc $row) => [
                'ma_danh_muc' => $row->ma_danh_muc,
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
                'values' => $values,
            ];
        }

        $maTrongFile = array_values(array_unique(array_map(
            fn (array $item) => $item['values']['ma_danh_muc'],
            $hopLe
        )));

        $maDaCo = $maTrongFile === []
            ? []
            : DanhMucTrangPhuc::query()
                ->whereIn('ma_danh_muc', $maTrongFile)
                ->pluck('ma_danh_muc')
                ->all();
        $maDaCo = array_fill_keys($maDaCo, true);
        $maDaXem = [];

        $canThem = [];
        foreach ($hopLe as $item) {
            $hang = $item['hang'];
            $values = $item['values'];
            $ma = $values['ma_danh_muc'];

            if (isset($maDaCo[$ma])) {
                $thatBai[] = $this->failItem($hang, 'Mã đã tồn tại.', $values);
                continue;
            }

            if (isset($maDaXem[$ma])) {
                $thatBai[] = $this->failItem(
                    $hang,
                    "Mã trùng với dòng {$maDaXem[$ma]} trong file.",
                    $values
                );
                continue;
            }

            $maDaXem[$ma] = $hang;
            $canThem[] = $item;
        }

        foreach ($canThem as $item) {
            try {
                $created = DanhMucTrangPhuc::query()->create($item['values']);
                $thanhCong[] = [
                    'hang' => $item['hang'],
                    'id' => $created->id,
                    'ma_danh_muc' => $created->ma_danh_muc,
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
        $maDanhMuc = trim((string) ($values['ma_danh_muc'] ?? ''));
        $tenDanhMuc = trim((string) ($values['ten_danh_muc'] ?? ''));

        if ($maDanhMuc === '') {
            return 'Thiếu mã danh mục.';
        }

        if (mb_strlen($maDanhMuc) > 50) {
            return 'Mã danh mục tối đa 50 ký tự.';
        }

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
