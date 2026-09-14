<?php

namespace App\Services\Excel\Types;

use App\Models\DanhMucTrangPhuc;
use App\Models\NhaCungCapTrangPhuc;
use App\Models\TrangPhuc;
use App\Services\Excel\BaseExcelType;
use Throwable;

class TrangPhucExcelType extends BaseExcelType
{
    /** @var array<string, string> */
    private const PHAN_LOAI_LABELS = [
        'dau_tu_tai_san' => 'Đầu tư tài sản',
        'vat_tu_tieu_hao' => 'Vật tư tiêu hao',
    ];

    /** @var array<string, string> */
    private const TINH_TRANG_LABELS = [
        'con_hang' => 'Còn hàng',
        'dang_cho_thue' => 'Đang cho thuê',
        'dang_sua_chua' => 'Đang sửa chữa',
        'ngung_su_dung' => 'Ngừng sử dụng',
    ];

    /** @var array<int, string> */
    private const TRANG_THAI_LABELS = [
        1 => 'Hoạt động',
        0 => 'Ngừng hoạt động',
    ];

    public function key(): string
    {
        return 'trang_phuc';
    }

    public function label(): string
    {
        return 'Trang phục';
    }

    public function filename(): string
    {
        return 'trang-phuc';
    }

    public function columns(): array
    {
        return [
            'ma_san_pham' => [
                'header' => 'Mã sản phẩm',
                'aliases' => ['ma', 'ma sp', 'mã sp'],
                'required' => true,
                'max' => 50,
            ],
            'ten_san_pham' => [
                'header' => 'Tên sản phẩm',
                'aliases' => ['ten', 'ten sp', 'tên sp'],
                'required' => true,
                'max' => 255,
            ],
            'hinh_anh' => [
                'header' => 'Hình ảnh',
                'aliases' => ['hinh anh', 'anh', 'ten hinh anh', 'image'],
                'required' => false,
                'max' => 240,
            ],
            'ma_danh_muc' => [
                'header' => 'Mã danh mục',
                'aliases' => ['danh muc', 'ma dm'],
                'required' => true,
                'max' => 50,
            ],
            'ma_nha_cung_cap' => [
                'header' => 'Mã nhà cung cấp',
                'aliases' => ['nha cung cap', 'ma ncc'],
                'required' => true,
                'max' => 50,
            ],
            'gia_tri' => [
                'header' => 'Giá trị',
                'aliases' => ['gia tri'],
                'required' => true,
            ],
            'gia_cho_thue' => [
                'header' => 'Giá cho thuê',
                'aliases' => ['gia thue', 'gia cho thue'],
                'required' => true,
            ],
            'phan_loai_chi_phi' => [
                'header' => 'Phân loại chi phí',
                'aliases' => ['phan loai'],
                'required' => true,
            ],
            'tinh_trang' => [
                'header' => 'Tình trạng',
                'aliases' => ['tinh trang'],
                'required' => true,
            ],
            'ghi_chu' => [
                'header' => 'Ghi chú',
                'aliases' => ['ghi chu', 'mo ta'],
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
                'title' => 'Danh sách danh mục',
                'headers' => [
                    'ma_danh_muc' => 'Mã danh mục',
                    'ten_danh_muc' => 'Tên danh mục',
                ],
                'rows' => DanhMucTrangPhuc::query()
                    ->orderBy('ma_danh_muc')
                    ->get(['ma_danh_muc', 'ten_danh_muc'])
                    ->map(fn (DanhMucTrangPhuc $row) => [
                        'ma_danh_muc' => $row->ma_danh_muc,
                        'ten_danh_muc' => $row->ten_danh_muc,
                    ])
                    ->all(),
            ],
            [
                'title' => 'Danh sách nhà cung cấp',
                'headers' => [
                    'ma_nha_cung_cap' => 'Mã nhà cung cấp',
                    'ten_nha_cung_cap' => 'Tên nhà cung cấp',
                ],
                'rows' => NhaCungCapTrangPhuc::query()
                    ->orderBy('ma_nha_cung_cap')
                    ->get(['ma_nha_cung_cap', 'ten_nha_cung_cap'])
                    ->map(fn (NhaCungCapTrangPhuc $row) => [
                        'ma_nha_cung_cap' => $row->ma_nha_cung_cap,
                        'ten_nha_cung_cap' => $row->ten_nha_cung_cap,
                    ])
                    ->all(),
            ],
            [
                'title' => 'Trạng thái',
                'headers' => [
                    'trang_thai' => 'Mã trạng thái',
                    'ten_trang_thai' => 'Tên trạng thái',
                ],
                'rows' => collect(self::TRANG_THAI_LABELS)
                    ->map(fn (string $ten, int $ma) => [
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
        return TrangPhuc::query()
            ->with([
                'danhMucTrangPhuc:id,ma_danh_muc',
                'nhaCungCapTrangPhuc:id,ma_nha_cung_cap',
            ])
            ->orderBy('id')
            ->get()
            ->map(fn (TrangPhuc $row) => [
                'ma_san_pham' => $row->ma_san_pham,
                'ten_san_pham' => $row->ten_san_pham,
                'hinh_anh' => $this->hinhAnhFileName($row->getRawOriginal('hinh_anh')),
                'ma_danh_muc' => $row->danhMucTrangPhuc?->ma_danh_muc,
                'ma_nha_cung_cap' => $row->nhaCungCapTrangPhuc?->ma_nha_cung_cap,
                'gia_tri' => $row->gia_tri,
                'gia_cho_thue' => $row->gia_cho_thue,
                'phan_loai_chi_phi' => self::PHAN_LOAI_LABELS[$row->phan_loai_chi_phi] ?? $row->phan_loai_chi_phi,
                'tinh_trang' => self::TINH_TRANG_LABELS[$row->tinh_trang] ?? $row->tinh_trang,
                'ghi_chu' => $row->ghi_chu,
                'trang_thai' => (int) $row->trang_thai,
            ])
            ->all();
    }

    public function importItems(array $items): array
    {
        $thanhCong = [];
        $thatBai = [];
        $hopLe = [];

        $danhMucByMa = $this->indexByMa(
            DanhMucTrangPhuc::query()->get(['id', 'ma_danh_muc']),
            'ma_danh_muc'
        );
        $nccByMa = $this->indexByMa(
            NhaCungCapTrangPhuc::query()->get(['id', 'ma_nha_cung_cap']),
            'ma_nha_cung_cap'
        );

        foreach ($items as $item) {
            $hang = (int) ($item['hang'] ?? $item['row'] ?? 0);
            $values = $this->itemValues($item);

            if ($this->isEmptyRow($values)) {
                continue;
            }

            $parsed = $this->parseRow($values, $danhMucByMa, $nccByMa);
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

        $maTrongFile = array_values(array_unique(array_map(
            fn (array $item) => $item['payload']['ma_san_pham'],
            $hopLe
        )));

        $maDaCo = $maTrongFile === []
            ? []
            : TrangPhuc::query()
                ->whereIn('ma_san_pham', $maTrongFile)
                ->pluck('ma_san_pham')
                ->all();
        $maDaCo = array_fill_keys($maDaCo, true);
        $maDaXem = [];

        $canThem = [];
        foreach ($hopLe as $item) {
            $hang = $item['hang'];
            $values = $item['values'];
            $ma = $item['payload']['ma_san_pham'];

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
                $created = TrangPhuc::query()->create($item['payload']);
                $thanhCong[] = [
                    'hang' => $item['hang'],
                    'id' => $created->id,
                    'ma_san_pham' => $created->ma_san_pham,
                    'ten_san_pham' => $created->ten_san_pham,
                    'hinh_anh' => $item['values']['hinh_anh'] ?? '',
                    'ma_danh_muc' => $item['values']['ma_danh_muc'],
                    'ma_nha_cung_cap' => $item['values']['ma_nha_cung_cap'],
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
     * @param  array<string, DanhMucTrangPhuc>  $danhMucByMa
     * @param  array<string, NhaCungCapTrangPhuc>  $nccByMa
     * @return array{loi: ?string, payload: array<string, mixed>}
     */
    private function parseRow(array $values, array $danhMucByMa, array $nccByMa): array
    {
        $maSanPham = trim((string) ($values['ma_san_pham'] ?? ''));
        $tenSanPham = trim((string) ($values['ten_san_pham'] ?? ''));
        $maDanhMuc = trim((string) ($values['ma_danh_muc'] ?? ''));
        $maNcc = trim((string) ($values['ma_nha_cung_cap'] ?? ''));

        if ($maSanPham === '') {
            return $this->parseFail('Thiếu mã sản phẩm.');
        }
        if (mb_strlen($maSanPham) > 50) {
            return $this->parseFail('Mã sản phẩm tối đa 50 ký tự.');
        }
        if ($tenSanPham === '') {
            return $this->parseFail('Thiếu tên sản phẩm.');
        }
        if (mb_strlen($tenSanPham) > 255) {
            return $this->parseFail('Tên sản phẩm tối đa 255 ký tự.');
        }
        if ($maDanhMuc === '') {
            return $this->parseFail('Thiếu mã danh mục.');
        }

        $danhMuc = $this->lookup($danhMucByMa, $maDanhMuc);
        if ($danhMuc === null) {
            return $this->parseFail("Không tìm thấy mã danh mục \"{$maDanhMuc}\".");
        }

        if ($maNcc === '') {
            return $this->parseFail('Thiếu mã nhà cung cấp.');
        }

        $ncc = $this->lookup($nccByMa, $maNcc);
        if ($ncc === null) {
            return $this->parseFail("Không tìm thấy mã nhà cung cấp \"{$maNcc}\".");
        }

        $giaTri = $this->parseInteger($values['gia_tri'] ?? '');
        if ($giaTri === null) {
            return $this->parseFail('Giá trị phải là số nguyên ≥ 0.');
        }

        $giaChoThue = $this->parseInteger($values['gia_cho_thue'] ?? '');
        if ($giaChoThue === null) {
            return $this->parseFail('Giá cho thuê phải là số nguyên ≥ 0.');
        }

        $phanLoai = $this->parseEnum(
            (string) ($values['phan_loai_chi_phi'] ?? ''),
            self::PHAN_LOAI_LABELS
        );
        if ($phanLoai === null) {
            return $this->parseFail('Phân loại chi phí không hợp lệ. Dùng: Đầu tư tài sản, Vật tư tiêu hao.');
        }

        $tinhTrang = $this->parseEnum(
            (string) ($values['tinh_trang'] ?? ''),
            self::TINH_TRANG_LABELS
        );
        if ($tinhTrang === null) {
            return $this->parseFail('Tình trạng không hợp lệ. Dùng: Còn hàng, Đang cho thuê, Đang sửa chữa, Ngừng sử dụng.');
        }

        $trangThai = $this->parseTrangThai((string) ($values['trang_thai'] ?? ''));
        if ($trangThai === null) {
            return $this->parseFail('Trạng thái không hợp lệ. Dùng 1 (Hoạt động) hoặc 0 (Ngừng hoạt động).');
        }

        $hinhAnh = $this->parseHinhAnh((string) ($values['hinh_anh'] ?? ''));
        if ($hinhAnh['loi'] !== null) {
            return $this->parseFail($hinhAnh['loi']);
        }

        $ghiChu = trim((string) ($values['ghi_chu'] ?? ''));

        return [
            'loi' => null,
            'payload' => [
                'hinh_anh' => $hinhAnh['path'],
                'ma_san_pham' => $maSanPham,
                'ten_san_pham' => $tenSanPham,
                'danh_muc' => $danhMuc->id,
                'nha_cung_cap' => $ncc->id,
                'gia_tri' => $giaTri,
                'gia_cho_thue' => $giaChoThue,
                'phan_loai_chi_phi' => $phanLoai,
                'tinh_trang' => $tinhTrang,
                'ghi_chu' => $ghiChu === '' ? null : $ghiChu,
                'trang_thai' => $trangThai,
            ],
        ];
    }

    /**
     * @return array{loi: ?string, path: ?string}
     */
    private function parseHinhAnh(string $value): array
    {
        $name = trim($value);
        if ($name === '') {
            return ['loi' => null, 'path' => null];
        }

        $name = str_replace('\\', '/', $name);
        $name = basename($name);

        if ($name === '' || $name === '.' || $name === '..' || str_contains($name, '..')) {
            return ['loi' => 'Tên hình ảnh không hợp lệ.', 'path' => null];
        }

        if (mb_strlen($name) > 240) {
            return ['loi' => 'Tên hình ảnh tối đa 240 ký tự.', 'path' => null];
        }

        return ['loi' => null, 'path' => 'trang-phuc/'.$name];
    }

    private function hinhAnhFileName(mixed $path): string
    {
        $raw = trim((string) ($path ?? ''));
        if ($raw === '') {
            return '';
        }

        return basename(str_replace('\\', '/', $raw));
    }

    /**
     * @return array{loi: string, payload: array<string, mixed>}
     */
    private function parseFail(string $loi): array
    {
        return ['loi' => $loi, 'payload' => []];
    }

    private function parseInteger(mixed $value): ?int
    {
        $text = trim((string) $value);
        if ($text === '') {
            return null;
        }

        $text = str_replace([' ', ','], '', $text);
        if (! is_numeric($text)) {
            return null;
        }

        $number = (float) $text;
        if ($number < 0 || floor($number) !== $number) {
            return null;
        }

        return (int) $number;
    }

    /**
     * @param  array<string, string>  $labels
     */
    private function parseEnum(string $value, array $labels): ?string
    {
        $normalized = $this->normalizeLookup($value);
        if ($normalized === '') {
            return null;
        }

        foreach ($labels as $code => $label) {
            if ($normalized === $this->normalizeLookup($code) || $normalized === $this->normalizeLookup($label)) {
                return $code;
            }
        }

        return null;
    }

    private function parseTrangThai(string $value): ?int
    {
        $normalized = $this->normalizeLookup($value);
        if ($normalized === '') {
            return 1;
        }

        foreach (self::TRANG_THAI_LABELS as $ma => $ten) {
            if ($normalized === (string) $ma || $normalized === $this->normalizeLookup($ten)) {
                return $ma;
            }
        }

        if (is_numeric($normalized)) {
            $number = (int) $normalized;
            if ((float) $normalized == $number && array_key_exists($number, self::TRANG_THAI_LABELS)) {
                return $number;
            }
        }

        return null;
    }

    /**
     * @param  iterable<int, object>  $rows
     * @return array<string, object>
     */
    private function indexByMa(iterable $rows, string $maField): array
    {
        $map = [];
        foreach ($rows as $row) {
            $ma = trim((string) $row->{$maField});
            if ($ma === '') {
                continue;
            }
            $map[$ma] = $row;
            $map[$this->normalizeLookup($ma)] = $row;
        }

        return $map;
    }

    /**
     * @param  array<string, object>  $map
     */
    private function lookup(array $map, string $ma): ?object
    {
        return $map[$ma] ?? $map[$this->normalizeLookup($ma)] ?? null;
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
