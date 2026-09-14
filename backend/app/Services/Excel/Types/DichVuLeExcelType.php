<?php

namespace App\Services\Excel\Types;

use App\Models\DichVuDanhSachDichVuLe;
use App\Models\DichVuLoaiDichVu;
use App\Models\LoaiHopDong;
use App\Services\Excel\BaseExcelType;
use Illuminate\Support\Collection;
use Throwable;

class DichVuLeExcelType extends BaseExcelType
{
    /** @var array<string, string> */
    private const TRANG_THAI_LABELS = [
        'dang_su_dung' => 'Đang sử dụng',
        'ngung_su_dung' => 'Ngừng sử dụng',
    ];

    public function key(): string
    {
        return 'dich_vu_le';
    }

    public function label(): string
    {
        return 'Dịch vụ lẻ';
    }

    public function filename(): string
    {
        return 'dich-vu-le';
    }

    public function columns(): array
    {
        return [
            'ma_dich_vu' => [
                'header' => 'Mã dịch vụ',
                'aliases' => ['ma', 'ma dv', 'mã', 'mã dịch vụ'],
                'required' => true,
                'max' => 50,
            ],
            'ten_dich_vu' => [
                'header' => 'Tên dịch vụ',
                'aliases' => ['ten', 'ten dich vu', 'tên'],
                'required' => true,
                'max' => 255,
            ],
            'loai_dich_vu_id' => [
                'header' => 'ID loại dịch vụ',
                'aliases' => ['id loai dich vu', 'loai dich vu id', 'id loại dịch vụ'],
                'required' => true,
            ],
            'ten_loai_dich_vu' => [
                'header' => 'Tên loại dịch vụ',
                'aliases' => ['loai dich vu', 'loại dịch vụ'],
                'required' => false,
                'max' => 255,
            ],
            'gia_goc' => [
                'header' => 'Giá gốc',
                'aliases' => ['gia goc'],
                'required' => true,
            ],
            'gia_khuyen_mai' => [
                'header' => 'Giá khuyến mãi',
                'aliases' => ['gia km', 'gia khuyen mai'],
                'required' => false,
            ],
            'loai_hop_dong_ids' => [
                'header' => 'ID loại hợp đồng',
                'aliases' => ['loai hop dong', 'id hop dong'],
                'required' => false,
            ],
            'trang_thai' => [
                'header' => 'Trạng thái',
                'aliases' => ['trang thai', 'ma trang thai'],
                'required' => false,
            ],
            'mo_ta' => [
                'header' => 'Mô tả',
                'aliases' => ['mo ta'],
                'required' => false,
            ],
            'ghi_chu' => [
                'header' => 'Ghi chú',
                'aliases' => ['ghi chu'],
                'required' => false,
            ],
        ];
    }

    public function extraSheets(): array
    {
        return [
            [
                'title' => 'Loại dịch vụ',
                'headers' => [
                    'loai_dich_vu_id' => 'ID loại dịch vụ',
                    'ten_dich_vu' => 'Tên loại dịch vụ',
                    'mo_ta' => 'Mô tả',
                    'trang_thai' => 'Trạng thái',
                ],
                'rows' => DichVuLoaiDichVu::query()
                    ->orderBy('id')
                    ->get(['id', 'ten_dich_vu', 'mo_ta', 'trang_thai'])
                    ->map(fn (DichVuLoaiDichVu $row) => [
                        'loai_dich_vu_id' => $row->id,
                        'ten_dich_vu' => $row->ten_dich_vu,
                        'mo_ta' => $row->mo_ta,
                        'trang_thai' => $row->trang_thai,
                    ])
                    ->all(),
            ],
            [
                'title' => 'Loại hợp đồng',
                'headers' => [
                    'id' => 'ID loại hợp đồng',
                    'ma_hop_dong' => 'Mã hợp đồng',
                    'ten_hop_dong' => 'Tên loại hợp đồng',
                ],
                'rows' => LoaiHopDong::query()
                    ->orderBy('id')
                    ->get(['id', 'ma_hop_dong', 'ten_hop_dong'])
                    ->map(fn (LoaiHopDong $row) => [
                        'id' => $row->id,
                        'ma_hop_dong' => $row->ma_hop_dong,
                        'ten_hop_dong' => $row->ten_hop_dong,
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
        return DichVuDanhSachDichVuLe::query()
            ->with('loaiDichVu:id,ten_dich_vu')
            ->orderBy('id')
            ->get()
            ->map(fn (DichVuDanhSachDichVuLe $row) => [
                'ma_dich_vu' => $row->ma_dich_vu,
                'ten_dich_vu' => $row->ten_dich_vu,
                'loai_dich_vu_id' => $row->loai_dich_vu_id,
                'ten_loai_dich_vu' => $row->loaiDichVu?->ten_dich_vu,
                'gia_goc' => $row->gia_goc,
                'gia_khuyen_mai' => $row->gia_khuyen_mai,
                'loai_hop_dong_ids' => $this->formatIds($row->loai_hop_dong_ids),
                'trang_thai' => $row->trang_thai,
                'mo_ta' => $row->mo_ta,
                'ghi_chu' => $row->ghi_chu,
            ])
            ->all();
    }

    public function importItems(array $items): array
    {
        $thanhCong = [];
        $thatBai = [];
        $hopLe = [];

        $loaiDichVuById = DichVuLoaiDichVu::query()
            ->get(['id', 'ten_dich_vu'])
            ->keyBy('id');
        $loaiHopDongIds = LoaiHopDong::query()->pluck('id')->all();
        $loaiHopDongIds = array_fill_keys($loaiHopDongIds, true);

        foreach ($items as $item) {
            $hang = (int) ($item['hang'] ?? $item['row'] ?? 0);
            $values = $this->itemValues($item);

            if ($this->isEmptyRow($values)) {
                continue;
            }

            $parsed = $this->parseRow($values, $loaiDichVuById, $loaiHopDongIds);
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
            fn (array $item) => $item['payload']['ma_dich_vu'],
            $hopLe
        )));

        $maDaCo = $maTrongFile === []
            ? []
            : DichVuDanhSachDichVuLe::query()
                ->whereIn('ma_dich_vu', $maTrongFile)
                ->pluck('ma_dich_vu')
                ->all();
        $maDaCo = array_fill_keys($maDaCo, true);
        $maDaXem = [];

        $canThem = [];
        foreach ($hopLe as $item) {
            $hang = $item['hang'];
            $values = $item['values'];
            $ma = $item['payload']['ma_dich_vu'];

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
                $created = DichVuDanhSachDichVuLe::query()->create($item['payload']);
                $thanhCong[] = [
                    'hang' => $item['hang'],
                    'id' => $created->id,
                    'ma_dich_vu' => $created->ma_dich_vu,
                    'ten_dich_vu' => $created->ten_dich_vu,
                    'loai_dich_vu_id' => $created->loai_dich_vu_id,
                    'ten_loai_dich_vu' => $item['values']['ten_loai_dich_vu'] ?? '',
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
     * @param  Collection<int, DichVuLoaiDichVu>  $loaiDichVuById
     * @param  array<int, true>  $loaiHopDongIds
     * @return array{loi: ?string, payload: array<string, mixed>}
     */
    private function parseRow(array $values, Collection $loaiDichVuById, array $loaiHopDongIds): array
    {
        $ma = trim((string) ($values['ma_dich_vu'] ?? ''));
        $ten = trim((string) ($values['ten_dich_vu'] ?? ''));
        $loaiIdRaw = trim((string) ($values['loai_dich_vu_id'] ?? ''));
        $tenLoai = trim((string) ($values['ten_loai_dich_vu'] ?? ''));

        if ($ma === '') {
            return $this->parseFail('Thiếu mã dịch vụ.');
        }
        if (mb_strlen($ma) > 50) {
            return $this->parseFail('Mã dịch vụ tối đa 50 ký tự.');
        }
        if ($ten === '') {
            return $this->parseFail('Thiếu tên dịch vụ.');
        }
        if (mb_strlen($ten) > 255) {
            return $this->parseFail('Tên dịch vụ tối đa 255 ký tự.');
        }
        if ($loaiIdRaw === '') {
            return $this->parseFail('Thiếu ID loại dịch vụ. Xem sheet "Loại dịch vụ".');
        }

        $loaiId = $this->parseId($loaiIdRaw);
        if ($loaiId === null) {
            return $this->parseFail('ID loại dịch vụ không hợp lệ.');
        }

        $loai = $loaiDichVuById->get($loaiId);
        if ($loai === null) {
            return $this->parseFail("Không tìm thấy loại dịch vụ với ID {$loaiId}.");
        }

        if ($tenLoai !== '' && $this->normalizeLookup($tenLoai) !== $this->normalizeLookup((string) $loai->ten_dich_vu)) {
            return $this->parseFail("Tên loại dịch vụ không khớp ID {$loaiId}.");
        }

        $giaGoc = $this->parseInteger($values['gia_goc'] ?? '');
        if ($giaGoc === null) {
            return $this->parseFail('Giá gốc phải là số nguyên ≥ 0.');
        }

        $giaKmRaw = trim((string) ($values['gia_khuyen_mai'] ?? ''));
        $giaKm = null;
        if ($giaKmRaw !== '') {
            $giaKm = $this->parseInteger($giaKmRaw);
            if ($giaKm === null) {
                return $this->parseFail('Giá khuyến mãi phải là số nguyên ≥ 0.');
            }
        }

        $hopDongIds = $this->parseIdList((string) ($values['loai_hop_dong_ids'] ?? ''));
        if ($hopDongIds === null) {
            return $this->parseFail('ID loại hợp đồng không hợp lệ. Dùng danh sách id, cách nhau bằng dấu phẩy — xem sheet "Loại hợp đồng".');
        }

        foreach ($hopDongIds as $hopDongId) {
            if (! isset($loaiHopDongIds[$hopDongId])) {
                return $this->parseFail("Không tìm thấy loại hợp đồng với ID {$hopDongId}.");
            }
        }

        $trangThai = $this->parseTrangThai((string) ($values['trang_thai'] ?? ''));
        if ($trangThai === null) {
            return $this->parseFail('Trạng thái không hợp lệ. Dùng dang_su_dung (Đang sử dụng) hoặc ngung_su_dung (Ngừng sử dụng).');
        }

        $moTa = trim((string) ($values['mo_ta'] ?? ''));
        $ghiChu = trim((string) ($values['ghi_chu'] ?? ''));

        return [
            'loi' => null,
            'payload' => [
                'ma_dich_vu' => $ma,
                'ten_dich_vu' => $ten,
                'loai_dich_vu_id' => $loai->id,
                'loai_hop_dong_ids' => $hopDongIds,
                'gia_goc' => $giaGoc,
                'gia_khuyen_mai' => $giaKm,
                'mo_ta' => $moTa === '' ? null : $moTa,
                'trang_thai' => $trangThai,
                'ghi_chu' => $ghiChu === '' ? null : $ghiChu,
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

    private function parseId(mixed $value): ?int
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
        if ($number < 1 || floor($number) !== $number) {
            return null;
        }

        return (int) $number;
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
     * @return list<int>|null
     */
    private function parseIdList(string $value): ?array
    {
        $text = trim($value);
        if ($text === '') {
            return [];
        }

        $parts = preg_split('/[;,\s]+/u', $text) ?: [];
        $ids = [];

        foreach ($parts as $part) {
            if ($part === '') {
                continue;
            }
            $id = $this->parseId($part);
            if ($id === null) {
                return null;
            }
            $ids[$id] = $id;
        }

        return array_values($ids);
    }

    /**
     * @param  list<int|string>|null  $ids
     */
    private function formatIds(?array $ids): string
    {
        if (! is_array($ids) || $ids === []) {
            return '';
        }

        return implode(', ', array_map(static fn ($id) => (string) $id, $ids));
    }

    private function parseTrangThai(string $value): ?string
    {
        $normalized = $this->normalizeLookup($value);
        if ($normalized === '') {
            return 'dang_su_dung';
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
