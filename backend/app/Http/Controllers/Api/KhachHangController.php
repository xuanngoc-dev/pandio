<?php

namespace App\Http\Controllers\Api;

use App\Models\HopDongChoThueTrangPhuc;
use App\Models\HopDongSuDungDichVu;
use App\Models\KhachHangNoteKhachMoi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

class KhachHangController extends BaseApiController
{
    public const NGUON_NOTE = 'note_khach_moi';

    public const NGUON_SDDV = 'hop_dong_sddv';

    public const NGUON_CHO_THUE = 'hop_dong_cho_thue';

    /**
     * Danh sách khách hàng gom từ note khách mới, HĐ dịch vụ và HĐ thuê trang phục.
     * Gom theo số điện thoại (chuẩn hoá); tên khách là mảng vì có thể khác nhau giữa các bảng.
     *
     * Query: page, per_page, keyword, loai_hop_dong
     */
    public function index(Request $request): JsonResponse
    {
        return $this->handleApi(function () use ($request) {
            $validated = $request->validate([
                'page' => ['sometimes', 'integer', 'min:1'],
                'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
                'keyword' => ['sometimes', 'nullable', 'string', 'max:255'],
                'loai_hop_dong' => ['sometimes', 'nullable', 'string', Rule::in([
                    self::NGUON_NOTE,
                    self::NGUON_SDDV,
                    self::NGUON_CHO_THUE,
                ])],
                'nguon' => ['sometimes', 'nullable', 'string', Rule::in([
                    self::NGUON_NOTE,
                    self::NGUON_SDDV,
                    self::NGUON_CHO_THUE,
                ])],
            ]);

            $page = (int) ($validated['page'] ?? 1);
            $perPage = (int) ($validated['per_page'] ?? 10);
            $keyword = mb_strtolower(trim((string) ($validated['keyword'] ?? '')));
            $loaiHopDong = $validated['loai_hop_dong'] ?? $validated['nguon'] ?? null;

            $customers = $this->aggregatedCustomers();

            if ($loaiHopDong) {
                $customers = $customers->filter(
                    fn (array $item) => in_array($loaiHopDong, $item['loai_hop_dong'], true)
                );
            }

            if ($keyword !== '') {
                $customers = $customers->filter(
                    fn (array $item) => $this->matchesKeyword($item, $keyword)
                );
            }

            $customers = $customers
                ->sortByDesc(fn (array $item) => $item['cap_nhat_gan_nhat'] ?? '')
                ->values();

            $total = $customers->count();
            $pageItems = $customers->forPage($page, $perPage)->values();

            $paginator = new LengthAwarePaginator(
                $pageItems,
                $total,
                $perPage,
                $page,
                [
                    'path' => $request->url(),
                    'query' => $request->query(),
                ]
            );

            return response()->json($paginator);

        }, 'lấy danh sách khách hàng');
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function aggregatedCustomers(): Collection
    {
        /** @var array<string, array<string, mixed>> $groups */
        $groups = [];

        foreach ($this->noteRows() as $row) {
            $this->mergeRow($groups, $row);
        }
        foreach ($this->hopDongSddvRows() as $row) {
            $this->mergeRow($groups, $row);
        }
        foreach ($this->hopDongChoThueRows() as $row) {
            $this->mergeRow($groups, $row);
        }

        return collect(array_values($groups))->map(function (array $group) {
            $tenKhach = array_values($group['ten_khach']);

            return [
                'id' => $group['id'],
                'sdt' => $group['sdt'],
                'ten_khach' => $tenKhach,
                'nguon_khach' => array_values($group['nguon_khach']),
                'loai_hop_dong' => array_values($group['loai_hop_dong']),
                'so_note' => count($group['note_khach_moi']),
                'so_hop_dong_sddv' => count($group['hop_dong_sddv']),
                'so_hop_dong_cho_thue' => count($group['hop_dong_cho_thue']),
                'tong_gia_tri_hop_dong' => $this->tongGiaTriHopDong($group),
                'cap_nhat_gan_nhat' => $group['cap_nhat_gan_nhat'],
                'note_khach_moi' => $group['note_khach_moi'],
                'hop_dong_sddv' => $group['hop_dong_sddv'],
                'hop_dong_cho_thue' => $group['hop_dong_cho_thue'],
            ];
        });
    }

    /**
     * @return list<array{source: string, id: int, ten: string, sdt: string, updated_at: ?string, record: array<string, mixed>}>
     */
    private function noteRows(): array
    {
        return KhachHangNoteKhachMoi::query()
            ->orderByDesc('id')
            ->get(['id', 'ten_khach', 'sdt', 'nguon_khach', 'ngay_hen_lich', 'ngay_den_thuc_te', 'trang_thai', 'tra_cuu_hd', 'created_at', 'updated_at'])
            ->map(function (KhachHangNoteKhachMoi $item) {
                $ten = trim((string) $item->ten_khach);
                $sdt = trim((string) $item->sdt);
                if ($ten === '' && $sdt === '') {
                    return null;
                }

                return [
                    'source' => self::NGUON_NOTE,
                    'id' => (int) $item->id,
                    'ten' => $ten,
                    'sdt' => $sdt,
                    'nguon_khach' => trim((string) $item->nguon_khach),
                    'updated_at' => optional($item->updated_at)?->toIso8601String(),
                    'record' => [
                        'id' => $item->id,
                        'ten_khach' => $ten !== '' ? $ten : null,
                        'sdt' => $sdt !== '' ? $sdt : null,
                        'nguon_khach' => trim((string) $item->nguon_khach) !== '' ? trim((string) $item->nguon_khach) : null,
                        'ngay_hen_lich' => optional($item->ngay_hen_lich)?->format('Y-m-d'),
                        'ngay_den_thuc_te' => optional($item->ngay_den_thuc_te)?->format('Y-m-d'),
                        'trang_thai' => $item->trang_thai,
                        'tra_cuu_hd' => $item->tra_cuu_hd,
                        'created_at' => optional($item->created_at)?->toIso8601String(),
                    ],
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @return list<array{source: string, id: int, ten: string, sdt: string, updated_at: ?string, record: array<string, mixed>}>
     */
    private function hopDongSddvRows(): array
    {
        return HopDongSuDungDichVu::query()
            ->orderByDesc('id')
            ->get([
                'id',
                'ma_hop_dong',
                'ten_khach_hang',
                'sdt_khach_hang',
                'thong_tin_hop_dong',
                'kenh_tiep_can',
                'trang_thai',
                'tong_tien',
                'created_at',
                'updated_at',
            ])
            ->map(function (HopDongSuDungDichVu $item) {
                [$ten, $sdt] = $this->tenVaSdtTuHopDongSddv($item);
                if ($ten === '' && $sdt === '') {
                    return null;
                }

                $nguonKhach = $this->nguonKhachTuHopDongSddv($item);

                return [
                    'source' => self::NGUON_SDDV,
                    'id' => (int) $item->id,
                    'ten' => $ten,
                    'sdt' => $sdt,
                    'nguon_khach' => $nguonKhach,
                    'updated_at' => optional($item->updated_at)?->toIso8601String(),
                    'record' => [
                        'id' => $item->id,
                        'ma_hop_dong' => $item->ma_hop_dong,
                        'ten_khach_hang' => $ten !== '' ? $ten : null,
                        'sdt_khach_hang' => $sdt !== '' ? $sdt : null,
                        'kenh_tiep_can' => $nguonKhach !== '' ? $nguonKhach : null,
                        'trang_thai' => $item->trang_thai,
                        'tong_tien' => (int) $item->tong_tien,
                        'created_at' => optional($item->created_at)?->toIso8601String(),
                    ],
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @return list<array{source: string, id: int, ten: string, sdt: string, updated_at: ?string, record: array<string, mixed>}>
     */
    private function hopDongChoThueRows(): array
    {
        return HopDongChoThueTrangPhuc::query()
            ->orderByDesc('id')
            ->get([
                'id',
                'ma_hop_dong',
                'ten_khach_hang',
                'sdt_khach_hang',
                'trang_thai',
                'ngay_thue',
                'tong_tien',
                'thanh_tien',
                'created_at',
                'updated_at',
            ])
            ->map(function (HopDongChoThueTrangPhuc $item) {
                $ten = trim((string) $item->ten_khach_hang);
                $sdt = trim((string) $item->sdt_khach_hang);
                if ($ten === '' && $sdt === '') {
                    return null;
                }

                $giaTri = (int) $item->thanh_tien;
                if ($giaTri <= 0) {
                    $giaTri = (int) $item->tong_tien;
                }

                return [
                    'source' => self::NGUON_CHO_THUE,
                    'id' => (int) $item->id,
                    'ten' => $ten,
                    'sdt' => $sdt,
                    'nguon_khach' => '',
                    'updated_at' => optional($item->updated_at)?->toIso8601String(),
                    'record' => [
                        'id' => $item->id,
                        'ma_hop_dong' => $item->ma_hop_dong,
                        'ten_khach_hang' => $ten !== '' ? $ten : null,
                        'sdt_khach_hang' => $sdt !== '' ? $sdt : null,
                        'trang_thai' => $item->trang_thai,
                        'ngay_thue' => optional($item->ngay_thue)?->format('Y-m-d'),
                        'tong_tien' => $giaTri,
                        'created_at' => optional($item->created_at)?->toIso8601String(),
                    ],
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @param  array<string, array<string, mixed>>  $groups
     * @param  array{source: string, id: int, ten: string, sdt: string, updated_at: ?string, record: array<string, mixed>}  $row
     */
    private function mergeRow(array &$groups, array $row): void
    {
        $key = $this->groupKey($row['sdt'], $row['source'], $row['id']);

        if (! isset($groups[$key])) {
            $groups[$key] = [
                'id' => $key,
                'sdt' => $this->preferSdt($row['sdt']),
                'ten_khach' => [],
                'nguon_khach' => [],
                'loai_hop_dong' => [],
                'note_khach_moi' => [],
                'hop_dong_sddv' => [],
                'hop_dong_cho_thue' => [],
                'cap_nhat_gan_nhat' => $row['updated_at'],
            ];
        }

        $group = &$groups[$key];
        $this->pushUniqueName($group['ten_khach'], $row['ten']);
        $this->pushUniqueName($group['nguon_khach'], (string) ($row['nguon_khach'] ?? ''));
        $group['loai_hop_dong'][$row['source']] = $row['source'];
        $group[$row['source']][] = $row['record'];
        $group['sdt'] = $this->preferSdt($group['sdt'], $row['sdt']);

        if (($row['updated_at'] ?? '') > ($group['cap_nhat_gan_nhat'] ?? '')) {
            $group['cap_nhat_gan_nhat'] = $row['updated_at'];
        }

        unset($group);
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function tenVaSdtTuHopDongSddv(HopDongSuDungDichVu $hopDong): array
    {
        $ten = trim((string) $hopDong->ten_khach_hang);
        $sdt = trim((string) $hopDong->sdt_khach_hang);
        $info = is_array($hopDong->thong_tin_hop_dong) ? $hopDong->thong_tin_hop_dong : [];

        if ($ten === '') {
            $ten = trim((string) ($info['hoTenKhachHang'] ?? $info['ho_ten_khach_hang'] ?? $info['hoTenKhach'] ?? ''));
        }
        if ($ten === '') {
            $chuRe = trim((string) ($info['tenChuRe'] ?? $info['ten_chu_re'] ?? ''));
            $coDau = trim((string) ($info['tenCoDau'] ?? $info['ten_co_dau'] ?? ''));
            $ten = trim(implode(' & ', array_filter([$chuRe, $coDau], fn ($v) => $v !== '')));
        }
        if ($sdt === '') {
            $sdt = trim((string) ($info['soDienThoai'] ?? $info['so_dien_thoai'] ?? $info['sdt'] ?? ''));
        }

        return [$ten, $sdt];
    }

    private function nguonKhachTuHopDongSddv(HopDongSuDungDichVu $hopDong): string
    {
        $kenh = trim((string) $hopDong->kenh_tiep_can);
        if ($kenh !== '') {
            return $kenh;
        }

        $info = is_array($hopDong->thong_tin_hop_dong) ? $hopDong->thong_tin_hop_dong : [];

        return trim((string) ($info['kenhTiepCan'] ?? $info['kenh_tiep_can'] ?? ''));
    }

    private function groupKey(string $sdt, string $source, int $id): string
    {
        $normalized = $this->normalizeSdt($sdt);
        if ($normalized !== '') {
            $last9 = strlen($normalized) >= 9 ? substr($normalized, -9) : $normalized;

            return 'sdt:'.$last9;
        }

        return "no-sdt:{$source}:{$id}";
    }

    private function normalizeSdt(string $sdt): string
    {
        $digits = preg_replace('/\D+/', '', $sdt) ?? '';
        if ($digits === '') {
            return '';
        }
        if (str_starts_with($digits, '84') && strlen($digits) >= 11) {
            $digits = '0'.substr($digits, 2);
        }

        return $digits;
    }

    private function preferSdt(?string $current, ?string $candidate = null): ?string
    {
        $current = trim((string) $current);
        $candidate = trim((string) $candidate);

        if ($candidate === '') {
            return $current !== '' ? $current : null;
        }
        if ($current === '') {
            return $candidate;
        }

        $currentNorm = $this->normalizeSdt($current);
        $candidateNorm = $this->normalizeSdt($candidate);
        $currentLocal = str_starts_with($currentNorm, '0');
        $candidateLocal = str_starts_with($candidateNorm, '0');

        if ($candidateLocal && ! $currentLocal) {
            return $candidate;
        }

        return $current;
    }

    /**
     * @param  array<string, mixed>  $group
     */
    private function tongGiaTriHopDong(array $group): int
    {
        $tong = 0;

        foreach ([...($group['hop_dong_sddv'] ?? []), ...($group['hop_dong_cho_thue'] ?? [])] as $hd) {
            if (! $this->hopDongTinhVaoTong((string) ($hd['trang_thai'] ?? ''))) {
                continue;
            }
            $tong += (int) ($hd['tong_tien'] ?? 0);
        }

        return $tong;
    }

    private function hopDongTinhVaoTong(string $trangThai): bool
    {
        return ! in_array($trangThai, ['moi_tao', 'nhap', 'da_huy'], true);
    }

    /**
     * @param  array<string, string>  $names
     */
    private function pushUniqueName(array &$names, string $ten): void
    {
        $ten = trim($ten);
        if ($ten === '') {
            return;
        }

        $key = mb_strtolower($ten);
        if (! isset($names[$key])) {
            $names[$key] = $ten;
        }
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function matchesKeyword(array $item, string $keyword): bool
    {
        if (str_contains(mb_strtolower((string) $item['sdt']), $keyword)) {
            return true;
        }

        foreach ($item['nguon_khach'] as $nguon) {
            if (str_contains(mb_strtolower((string) $nguon), $keyword)) {
                return true;
            }
        }

        foreach ($item['ten_khach'] as $ten) {
            if (str_contains(mb_strtolower((string) $ten), $keyword)) {
                return true;
            }
        }

        foreach ($item['hop_dong_sddv'] as $hd) {
            if (str_contains(mb_strtolower((string) ($hd['ma_hop_dong'] ?? '')), $keyword)) {
                return true;
            }
        }
        foreach ($item['hop_dong_cho_thue'] as $hd) {
            if (str_contains(mb_strtolower((string) ($hd['ma_hop_dong'] ?? '')), $keyword)) {
                return true;
            }
        }
        foreach ($item['note_khach_moi'] as $note) {
            if (str_contains(mb_strtolower((string) ($note['tra_cuu_hd'] ?? '')), $keyword)) {
                return true;
            }
        }

        return false;
    }
}
