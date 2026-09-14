<?php

namespace App\Services\Excel\Types;

use App\Models\NhaCungCapTrangPhuc;
use App\Services\Excel\BaseExcelType;
use Throwable;

class NhaCungCapTrangPhucExcelType extends BaseExcelType
{
    public function key(): string
    {
        return 'nha_cung_cap_trang_phuc';
    }

    public function label(): string
    {
        return 'Nhà cung cấp trang phục';
    }

    public function filename(): string
    {
        return 'nha-cung-cap-trang-phuc';
    }

    public function columns(): array
    {
        return [
            'ma_nha_cung_cap' => [
                'header' => 'Mã nhà cung cấp',
                'aliases' => ['ma', 'ma ncc', 'mã', 'mã ncc'],
                'required' => true,
                'max' => 50,
            ],
            'ten_nha_cung_cap' => [
                'header' => 'Tên nhà cung cấp',
                'aliases' => ['ten', 'ten ncc', 'tên', 'tên ncc'],
                'required' => true,
                'max' => 255,
            ],
            'so_dien_thoai' => [
                'header' => 'Số điện thoại',
                'aliases' => ['sdt', 'dien thoai', 'điện thoại'],
                'required' => false,
                'max' => 20,
            ],
            'email' => [
                'header' => 'Email',
                'aliases' => ['mail'],
                'required' => false,
                'max' => 255,
            ],
            'dia_chi' => [
                'header' => 'Địa chỉ',
                'aliases' => ['dia chi'],
                'required' => false,
                'max' => 255,
            ],
            'ghi_chu' => [
                'header' => 'Ghi chú',
                'aliases' => ['ghi chu', 'mo ta', 'mô tả'],
                'required' => false,
            ],
        ];
    }

    public function exportRows(): array
    {
        return NhaCungCapTrangPhuc::query()
            ->orderBy('id')
            ->get([
                'ma_nha_cung_cap',
                'ten_nha_cung_cap',
                'so_dien_thoai',
                'email',
                'dia_chi',
                'ghi_chu',
            ])
            ->map(fn (NhaCungCapTrangPhuc $row) => [
                'ma_nha_cung_cap' => $row->ma_nha_cung_cap,
                'ten_nha_cung_cap' => $row->ten_nha_cung_cap,
                'so_dien_thoai' => $row->so_dien_thoai,
                'email' => $row->email,
                'dia_chi' => $row->dia_chi,
                'ghi_chu' => $row->ghi_chu,
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

        $maTrongFile = array_values(array_unique(array_map(
            fn (array $item) => $item['values']['ma_nha_cung_cap'],
            $hopLe
        )));

        $maDaCo = $maTrongFile === []
            ? []
            : NhaCungCapTrangPhuc::query()
                ->whereIn('ma_nha_cung_cap', $maTrongFile)
                ->pluck('ma_nha_cung_cap')
                ->all();
        $maDaCo = array_fill_keys($maDaCo, true);
        $maDaXem = [];

        $canThem = [];
        foreach ($hopLe as $item) {
            $hang = $item['hang'];
            $values = $item['values'];
            $ma = $values['ma_nha_cung_cap'];

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
                $created = NhaCungCapTrangPhuc::query()->create($item['values']);
                $thanhCong[] = [
                    'hang' => $item['hang'],
                    'id' => $created->id,
                    'ma_nha_cung_cap' => $created->ma_nha_cung_cap,
                    'ten_nha_cung_cap' => $created->ten_nha_cung_cap,
                    'so_dien_thoai' => $created->so_dien_thoai,
                    'email' => $created->email,
                    'dia_chi' => $created->dia_chi,
                    'ghi_chu' => $created->ghi_chu,
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
        $ma = trim((string) ($values['ma_nha_cung_cap'] ?? ''));
        $ten = trim((string) ($values['ten_nha_cung_cap'] ?? ''));
        $soDienThoai = trim((string) ($values['so_dien_thoai'] ?? ''));
        $email = trim((string) ($values['email'] ?? ''));
        $diaChi = trim((string) ($values['dia_chi'] ?? ''));

        if ($ma === '') {
            return 'Thiếu mã nhà cung cấp.';
        }

        if (mb_strlen($ma) > 50) {
            return 'Mã nhà cung cấp tối đa 50 ký tự.';
        }

        if ($ten === '') {
            return 'Thiếu tên nhà cung cấp.';
        }

        if (mb_strlen($ten) > 255) {
            return 'Tên nhà cung cấp tối đa 255 ký tự.';
        }

        if (mb_strlen($soDienThoai) > 20) {
            return 'Số điện thoại tối đa 20 ký tự.';
        }

        if ($email !== '' && ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return 'Email không hợp lệ.';
        }

        if (mb_strlen($email) > 255) {
            return 'Email tối đa 255 ký tự.';
        }

        if (mb_strlen($diaChi) > 255) {
            return 'Địa chỉ tối đa 255 ký tự.';
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    private function normalizeValues(array $values): array
    {
        return [
            'ma_nha_cung_cap' => trim((string) ($values['ma_nha_cung_cap'] ?? '')),
            'ten_nha_cung_cap' => trim((string) ($values['ten_nha_cung_cap'] ?? '')),
            'so_dien_thoai' => $this->nullableString($values['so_dien_thoai'] ?? null),
            'email' => $this->nullableString($values['email'] ?? null),
            'dia_chi' => $this->nullableString($values['dia_chi'] ?? null),
            'ghi_chu' => $this->nullableString($values['ghi_chu'] ?? null),
        ];
    }

    private function nullableString(mixed $value): ?string
    {
        $text = trim((string) ($value ?? ''));

        return $text === '' ? null : $text;
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
