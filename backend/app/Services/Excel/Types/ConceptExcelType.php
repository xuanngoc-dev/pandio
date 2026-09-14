<?php

namespace App\Services\Excel\Types;

use App\Models\Concept;
use App\Models\DanhMucConcept;
use App\Services\Excel\BaseExcelType;
use Illuminate\Support\Collection;
use Throwable;

class ConceptExcelType extends BaseExcelType
{
    /** @var array<string, string> */
    private const TRANG_THAI_LABELS = [
        'dang_su_dung' => 'Đang sử dụng',
        'ngung_su_dung' => 'Ngừng sử dụng',
    ];

    public function key(): string
    {
        return 'concept';
    }

    public function label(): string
    {
        return 'Concept';
    }

    public function filename(): string
    {
        return 'concept';
    }

    public function columns(): array
    {
        return [
            'ma_concept' => [
                'header' => 'Mã concept',
                'aliases' => ['ma', 'ma cp', 'mã', 'mã concept'],
                'required' => true,
                'max' => 50,
            ],
            'ten_concept' => [
                'header' => 'Tên concept',
                'aliases' => ['ten', 'ten concept', 'tên'],
                'required' => true,
                'max' => 255,
            ],
            'hinh_anh' => [
                'header' => 'Hình ảnh',
                'aliases' => ['hinh anh', 'anh', 'ten hinh anh', 'image'],
                'required' => false,
                'max' => 240,
            ],
            'loai_concept' => [
                'header' => 'ID danh mục',
                'aliases' => ['id danh muc', 'loai concept', 'id loai', 'id dm'],
                'required' => true,
            ],
            'ten_danh_muc' => [
                'header' => 'Tên danh mục',
                'aliases' => ['danh muc', 'loai', 'tên danh mục'],
                'required' => false,
                'max' => 255,
            ],
            'dia_diem' => [
                'header' => 'Địa điểm',
                'aliases' => ['dia diem'],
                'required' => false,
                'max' => 255,
            ],
            'trang_thai' => [
                'header' => 'Trạng thái',
                'aliases' => ['trang thai', 'ma trang thai'],
                'required' => false,
            ],
            'mo_ta' => [
                'header' => 'Mô tả',
                'aliases' => ['mo ta', 'ghi chu', 'ghi chú'],
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
                    'loai_concept' => 'ID danh mục',
                    'ten_danh_muc' => 'Tên danh mục',
                    'mo_ta' => 'Mô tả',
                ],
                'rows' => DanhMucConcept::query()
                    ->orderBy('id')
                    ->get(['id', 'ten_danh_muc', 'mo_ta'])
                    ->map(fn (DanhMucConcept $row) => [
                        'loai_concept' => $row->id,
                        'ten_danh_muc' => $row->ten_danh_muc,
                        'mo_ta' => $row->mo_ta,
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
        return Concept::query()
            ->with('danhMuc:id,ten_danh_muc')
            ->orderBy('id')
            ->get()
            ->map(fn (Concept $row) => [
                'ma_concept' => $row->ma_concept,
                'ten_concept' => $row->ten_concept,
                'hinh_anh' => $this->hinhAnhFileName($row->getRawOriginal('hinh_anh')),
                'loai_concept' => $row->loai_concept,
                'ten_danh_muc' => $row->danhMuc?->ten_danh_muc,
                'dia_diem' => $row->dia_diem,
                'trang_thai' => $row->trang_thai,
                'mo_ta' => $row->mo_ta,
            ])
            ->all();
    }

    public function importItems(array $items): array
    {
        $thanhCong = [];
        $thatBai = [];
        $hopLe = [];

        $danhMucById = DanhMucConcept::query()
            ->get(['id', 'ten_danh_muc'])
            ->keyBy('id');

        foreach ($items as $item) {
            $hang = (int) ($item['hang'] ?? $item['row'] ?? 0);
            $values = $this->itemValues($item);

            if ($this->isEmptyRow($values)) {
                continue;
            }

            $parsed = $this->parseRow($values, $danhMucById);
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
            fn (array $item) => $item['payload']['ma_concept'],
            $hopLe
        )));

        $maDaCo = $maTrongFile === []
            ? []
            : Concept::query()
                ->whereIn('ma_concept', $maTrongFile)
                ->pluck('ma_concept')
                ->all();
        $maDaCo = array_fill_keys($maDaCo, true);
        $maDaXem = [];

        $canThem = [];
        foreach ($hopLe as $item) {
            $hang = $item['hang'];
            $values = $item['values'];
            $ma = $item['payload']['ma_concept'];

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
                $created = Concept::query()->create($item['payload']);
                $thanhCong[] = [
                    'hang' => $item['hang'],
                    'id' => $created->id,
                    'ma_concept' => $created->ma_concept,
                    'ten_concept' => $created->ten_concept,
                    'hinh_anh' => $item['values']['hinh_anh'] ?? '',
                    'loai_concept' => $created->loai_concept,
                    'ten_danh_muc' => $item['values']['ten_danh_muc'] ?? '',
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
     * @param  Collection<int, DanhMucConcept>  $danhMucById
     * @return array{loi: ?string, payload: array<string, mixed>}
     */
    private function parseRow(array $values, Collection $danhMucById): array
    {
        $maConcept = trim((string) ($values['ma_concept'] ?? ''));
        $tenConcept = trim((string) ($values['ten_concept'] ?? ''));
        $loaiConceptRaw = trim((string) ($values['loai_concept'] ?? ''));
        $tenDanhMuc = trim((string) ($values['ten_danh_muc'] ?? ''));

        if ($maConcept === '') {
            return $this->parseFail('Thiếu mã concept.');
        }
        if (mb_strlen($maConcept) > 50) {
            return $this->parseFail('Mã concept tối đa 50 ký tự.');
        }
        if ($tenConcept === '') {
            return $this->parseFail('Thiếu tên concept.');
        }
        if (mb_strlen($tenConcept) > 255) {
            return $this->parseFail('Tên concept tối đa 255 ký tự.');
        }
        if ($loaiConceptRaw === '') {
            return $this->parseFail('Thiếu ID danh mục. Xem sheet "Danh sách danh mục".');
        }

        $loaiConcept = $this->parseId($loaiConceptRaw);
        if ($loaiConcept === null) {
            return $this->parseFail('ID danh mục không hợp lệ.');
        }

        $danhMuc = $danhMucById->get($loaiConcept);
        if ($danhMuc === null) {
            return $this->parseFail("Không tìm thấy danh mục với ID {$loaiConcept}.");
        }

        if ($tenDanhMuc !== '' && $this->normalizeLookup($tenDanhMuc) !== $this->normalizeLookup((string) $danhMuc->ten_danh_muc)) {
            return $this->parseFail("Tên danh mục không khớp ID {$loaiConcept}.");
        }

        $diaDiem = trim((string) ($values['dia_diem'] ?? ''));
        if (mb_strlen($diaDiem) > 255) {
            return $this->parseFail('Địa điểm tối đa 255 ký tự.');
        }

        $trangThai = $this->parseTrangThai((string) ($values['trang_thai'] ?? ''));
        if ($trangThai === null) {
            return $this->parseFail('Trạng thái không hợp lệ. Dùng dang_su_dung (Đang sử dụng) hoặc ngung_su_dung (Ngừng sử dụng).');
        }

        $hinhAnh = $this->parseHinhAnh((string) ($values['hinh_anh'] ?? ''));
        if ($hinhAnh['loi'] !== null) {
            return $this->parseFail($hinhAnh['loi']);
        }

        $moTa = trim((string) ($values['mo_ta'] ?? ''));

        return [
            'loi' => null,
            'payload' => [
                'hinh_anh' => $hinhAnh['path'],
                'loai_concept' => $danhMuc->id,
                'ma_concept' => $maConcept,
                'ten_concept' => $tenConcept,
                'dia_diem' => $diaDiem === '' ? null : $diaDiem,
                'trang_thai' => $trangThai,
                'mo_ta' => $moTa === '' ? null : $moTa,
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

        if (preg_match('#^(https?:)?//#i', $name)) {
            if (mb_strlen($name) > 1000) {
                return ['loi' => 'Link hình ảnh tối đa 1000 ký tự.', 'path' => null];
            }

            return ['loi' => null, 'path' => $name];
        }

        $name = str_replace('\\', '/', $name);
        $name = basename($name);

        if ($name === '' || $name === '.' || $name === '..' || str_contains($name, '..')) {
            return ['loi' => 'Tên hình ảnh không hợp lệ.', 'path' => null];
        }

        if (mb_strlen($name) > 240) {
            return ['loi' => 'Tên hình ảnh tối đa 240 ký tự.', 'path' => null];
        }

        return ['loi' => null, 'path' => 'concept/'.$name];
    }

    private function hinhAnhFileName(mixed $path): string
    {
        $raw = trim((string) ($path ?? ''));
        if ($raw === '') {
            return '';
        }

        if (preg_match('#^(https?:)?//#i', $raw) && ! str_contains($raw, '/storage/')) {
            return $raw;
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
