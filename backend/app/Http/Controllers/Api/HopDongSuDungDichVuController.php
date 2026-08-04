<?php

namespace App\Http\Controllers\Api;

use App\Models\HopDongDongSddvCombo;
use App\Models\HopDongDongSddvConcept;
use App\Models\HopDongDongSddvDichVu;
use App\Models\HopDongDongSddvTrangPhuc;
use App\Models\HopDongSuDungDichVu;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class HopDongSuDungDichVuController extends BaseApiController
{
    /**
     * Danh sách hợp đồng sử dụng dịch vụ — phân trang + tìm kiếm.
     *
     * Query: page, per_page, keyword, loai_hop_dong_id, trang_thai, chi_nhap, tu_ngay, den_ngay
     */
    public function index(Request $request): JsonResponse
    {
        return $this->handleApi(function () use ($request) {
            $validated = $request->validate([
                'page' => ['sometimes', 'integer', 'min:1'],
                'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
                'keyword' => ['sometimes', 'nullable', 'string', 'max:255'],
                'loai_hop_dong_id' => ['sometimes', 'nullable', 'integer', 'exists:danh_muc_loai_hop_dong,id'],
                'trang_thai' => ['sometimes', 'nullable', 'string', 'max:50'],
                'chi_nhap' => ['sometimes', 'boolean'],
                'tu_ngay' => ['sometimes', 'nullable', 'date'],
                'den_ngay' => ['sometimes', 'nullable', 'date', 'after_or_equal:tu_ngay'],
            ]);

            $perPage = $validated['per_page'] ?? 10;
            $keyword = trim((string) ($validated['keyword'] ?? ''));
            $loaiHopDongId = $validated['loai_hop_dong_id'] ?? null;
            $trangThai = $validated['trang_thai'] ?? null;
            $chiNhap = $request->boolean('chi_nhap');
            $tuNgay = $validated['tu_ngay'] ?? null;
            $denNgay = $validated['den_ngay'] ?? null;

            $query = HopDongSuDungDichVu::query()
                ->with($this->detailRelations())
                ->when($keyword !== '', function ($q) use ($keyword) {
                    $q->where(function ($inner) use ($keyword) {
                        $inner->where('ma_hop_dong', 'like', "%{$keyword}%")
                            ->orWhere('ten_khach_hang', 'like', "%{$keyword}%")
                            ->orWhere('sdt_khach_hang', 'like', "%{$keyword}%")
                            ->orWhere('dia_chi', 'like', "%{$keyword}%")
                            ->orWhere('kenh_tiep_can', 'like', "%{$keyword}%")
                            ->orWhere('ma_giam_gia', 'like', "%{$keyword}%")
                            ->orWhere('luot_gioi_thieu', 'like', "%{$keyword}%")
                            ->orWhere('thong_tin_hop_dong', 'like', "%{$keyword}%");
                    });
                })
                ->when($loaiHopDongId, fn ($q) => $q->where('loai_hop_dong_id', $loaiHopDongId))
                ->when($tuNgay, fn ($q) => $q->whereDate('created_at', '>=', $tuNgay))
                ->when($denNgay, fn ($q) => $q->whereDate('created_at', '<=', $denNgay))
                ->when($chiNhap, function ($q) {
                    // Nháp: moi_tao/nhap và đã chọn loại hợp đồng
                    $q->whereIn('trang_thai', ['moi_tao', 'nhap'])
                        ->whereNotNull('loai_hop_dong_id');
                }, function ($q) use ($trangThai) {
                    if ($trangThai) {
                        $q->where('trang_thai', $trangThai);
                    } else {
                        // Mặc định ẩn hợp đồng nháp / mới tạo
                        $q->whereNotIn('trang_thai', ['moi_tao', 'nhap']);
                    }
                })
                ->orderByDesc('id');

            return response()->json($query->paginate($perPage));

        }, 'lấy danh sách hợp đồng sử dụng dịch vụ');
    }

    /**
     * Chi tiết một hợp đồng sử dụng dịch vụ.
     */
    public function show(HopDongSuDungDichVu $hop_dong_su_dung_dich_vu): JsonResponse
    {
        return $this->handleApi(function () use ($hop_dong_su_dung_dich_vu) {
            return response()->json($this->loadDetail($hop_dong_su_dung_dich_vu));

        }, 'lấy chi tiết hợp đồng sử dụng dịch vụ');
    }

    /**
     * Khởi tạo hợp đồng nháp + sinh mã HDSDDV_DDMMYYYY{id}.
     */
    public function khoiTao(Request $request): JsonResponse
    {
        return $this->handleApi(function () use ($request) {
            $hopDong = DB::transaction(function () use ($request) {
                $hopDong = HopDongSuDungDichVu::create([
                    'ma_hop_dong' => 'TEMP_'.Str::upper(Str::random(16)),
                    'loai_hop_dong_id' => null,
                    'nguoi_tao_id' => $request->user()->id,
                    'trang_thai' => 'moi_tao',
                    'tong_tien' => 0,
                    'chiet_khau' => 0,
                    'khuyen_mai_theo_ma_giam_gia' => 0,
                    'tien_coc' => 0,
                ]);

                $hopDong->update([
                    'ma_hop_dong' => HopDongSuDungDichVu::buildMaHopDong($hopDong->id),
                ]);

                return $hopDong;
            });

            return response()->json($this->loadDetail($hopDong), 201);

        }, 'khởi tạo hợp đồng sử dụng dịch vụ');
    }

    /**
     * Tạo hợp đồng sử dụng dịch vụ mới.
     */
    public function store(Request $request): JsonResponse
    {
        return $this->handleApi(function () use ($request) {
            $validated = $this->validatePayload($request);
            $validated['nguoi_tao_id'] = $request->user()->id;
            [$combos, $dichVu, $concepts, $trangPhucs] = $this->extractNestedPayload($validated);

            $hopDong = DB::transaction(function () use ($validated, $combos, $dichVu, $concepts, $trangPhucs) {
                $hopDong = HopDongSuDungDichVu::create($validated);
                $this->syncNestedRelations($hopDong, $combos, $dichVu, $concepts, $trangPhucs);

                return $hopDong;
            });

            return response()->json($this->loadDetail($hopDong), 201);

        }, 'tạo hợp đồng sử dụng dịch vụ');
    }

    /**
     * Cập nhật hợp đồng sử dụng dịch vụ.
     */
    public function update(Request $request, HopDongSuDungDichVu $hop_dong_su_dung_dich_vu): JsonResponse
    {
        return $this->handleApi(function () use ($request, $hop_dong_su_dung_dich_vu) {
            $validated = $this->validatePayload($request, $hop_dong_su_dung_dich_vu->id, true);
            unset($validated['nguoi_tao_id'], $validated['ma_hop_dong']);
            [$combos, $dichVu, $concepts, $trangPhucs] = $this->extractNestedPayload($validated);

            DB::transaction(function () use ($hop_dong_su_dung_dich_vu, $validated, $combos, $dichVu, $concepts, $trangPhucs) {
                if ($validated !== []) {
                    $hop_dong_su_dung_dich_vu->update($validated);
                }
                $this->syncNestedRelations($hop_dong_su_dung_dich_vu, $combos, $dichVu, $concepts, $trangPhucs);
            });

            return response()->json($this->loadDetail($hop_dong_su_dung_dich_vu->fresh()));

        }, 'cập nhật hợp đồng sử dụng dịch vụ');
    }

    /**
     * Xóa hợp đồng sử dụng dịch vụ.
     */
    public function destroy(HopDongSuDungDichVu $hop_dong_su_dung_dich_vu): JsonResponse
    {
        return $this->handleApi(function () use ($hop_dong_su_dung_dich_vu) {
            $hop_dong_su_dung_dich_vu->delete();

            return response()->json(['message' => 'Đã xóa hợp đồng sử dụng dịch vụ.']);

        }, 'xóa hợp đồng sử dụng dịch vụ');
    }

    /**
     * @return array<int, string>
     */
    private function detailRelations(): array
    {
        return [
            'loaiHopDong:id,ten_hop_dong,ma_hop_dong',
            'nguoiTao:id,name,phone',
            'combos.combo:id,ma_nhom,ten_nhom,gia_goc,gia_khuyen_mai',
            'dichVu.dichVu:id,ma_dich_vu,ten_dich_vu,gia_goc,gia_khuyen_mai',
            'concepts.concept:id,ma_concept,ten_concept,dia_diem,hinh_anh,trang_thai',
            'trangPhucs.trangPhuc:id,ma_san_pham,ten_san_pham,gia_cho_thue,hinh_anh,trang_thai',
        ];
    }

    private function loadDetail(HopDongSuDungDichVu $hopDong): HopDongSuDungDichVu
    {
        return $hopDong->load($this->detailRelations());
    }

    /**
     * @return array<string, mixed>
     */
    private function validatePayload(Request $request, ?int $ignoreId = null, bool $isUpdate = false): array
    {
        $maHopDongRule = Rule::unique('hop_dong_su_dung_dich_vu', 'ma_hop_dong');
        if ($ignoreId !== null) {
            $maHopDongRule = $maHopDongRule->ignore($ignoreId);
        }

        $validated = $request->validate([
            'ma_hop_dong' => [$isUpdate ? 'sometimes' : 'required', 'string', 'max:100', $maHopDongRule],
            'loai_hop_dong_id' => [$isUpdate ? 'sometimes' : 'required', 'nullable', 'integer', 'exists:danh_muc_loai_hop_dong,id'],
            'ten_khach_hang' => ['nullable', 'string', 'max:255'],
            'sdt_khach_hang' => ['nullable', 'string', 'max:20'],
            'dia_chi' => ['nullable', 'string', 'max:255'],
            'kenh_tiep_can' => ['nullable', 'string', 'max:255'],
            'thong_tin_hop_dong' => ['nullable', 'array'],
            'thong_tin_dieu_phoi' => ['nullable', 'array'],
            'nguoi_tham_gia_ids' => ['nullable', 'array'],
            'nguoi_tham_gia_ids.*' => ['integer', 'exists:users,id'],
            'trang_thai' => ['sometimes', 'string', Rule::in([
                'moi_tao',
                'nhap',
                'da_coc',
                'dang_thuc_hien',
                'da_huy',
                'hoan_thanh',
            ])],
            'tong_tien' => ['nullable', 'integer', 'min:0'],
            'phat_sinh' => ['nullable', 'integer', 'min:0'],
            'chiet_khau' => ['nullable', 'integer', 'min:0'],
            'ma_giam_gia' => ['nullable', 'string', 'max:100'],
            'khuyen_mai_theo_ma_giam_gia' => ['nullable', 'integer', 'min:0'],
            'tien_coc' => ['nullable', 'integer', 'min:0'],
            'so_tien_thanh_toan_lan_1' => ['nullable', 'integer', 'min:0'],
            'so_tien_thanh_toan_lan_2' => ['nullable', 'integer', 'min:0'],
            'so_tien_thanh_toan_lan_3' => ['nullable', 'integer', 'min:0'],
            'thoi_gian_thanh_toan_lan_1' => ['nullable', 'date'],
            'thoi_gian_thanh_toan_lan_2' => ['nullable', 'date'],
            'thoi_gian_thanh_toan_lan_3' => ['nullable', 'date'],
            'hinh_thuc_coc' => ['nullable', 'string', Rule::in(['online', 'offline'])],
            'han_thanh_toan_lan_2' => ['nullable', 'date'],
            'han_thanh_toan_lan_3' => ['nullable', 'date'],
            'qua_tang_kem' => ['nullable', 'string', 'max:255'],
            'yeu_cau_dac_biet' => ['nullable', 'string'],
            'ghi_chu_sale' => ['nullable', 'string'],
            'luot_gioi_thieu' => ['nullable', 'string', 'max:255'],
            'combos' => [$isUpdate ? 'sometimes' : 'nullable', 'array'],
            'combos.*.combo_id' => ['required', 'integer', 'exists:dich_vu_danh_sach_dich_nhom_dich_vu,id', 'distinct'],
            'combos.*.so_luong' => ['required', 'integer', 'min:1', 'max:999'],
            'combos.*.thanh_tien' => ['required', 'integer', 'min:0'],
            'combos.*.ghi_chu' => ['nullable', 'string', 'max:100'],
            'dich_vu' => [$isUpdate ? 'sometimes' : 'nullable', 'array'],
            'dich_vu.*.dich_vu_id' => ['required', 'integer', 'exists:dich_vu_danh_sach_dich_vu_le,id', 'distinct'],
            'dich_vu.*.so_luong' => ['required', 'integer', 'min:1', 'max:999'],
            'dich_vu.*.thanh_tien' => ['required', 'integer', 'min:0'],
            'dich_vu.*.ghi_chu' => ['nullable', 'string', 'max:100'],
            'concepts' => [$isUpdate ? 'sometimes' : 'nullable', 'array'],
            'concepts.*.concept_id' => ['required', 'integer', 'exists:concept,id', 'distinct'],
            'trang_phucs' => [$isUpdate ? 'sometimes' : 'nullable', 'array'],
            'trang_phucs.*.trang_phuc_id' => ['required', 'integer', 'exists:trang_phuc,id', 'distinct'],
            'trang_phucs.*.ngay_bat_dau' => ['nullable', 'date'],
            'trang_phucs.*.ngay_ket_thuc' => ['nullable', 'date', 'after_or_equal:trang_phucs.*.ngay_bat_dau'],
        ]);

        foreach ([
            'tong_tien',
            'phat_sinh',
            'chiet_khau',
            'khuyen_mai_theo_ma_giam_gia',
            'tien_coc',
            'so_tien_thanh_toan_lan_1',
            'so_tien_thanh_toan_lan_2',
            'so_tien_thanh_toan_lan_3',
        ] as $moneyField) {
            if (array_key_exists($moneyField, $validated)) {
                $validated[$moneyField] = (int) ($validated[$moneyField] ?? 0);
            }
        }
        if (! $isUpdate) {
            $validated['trang_thai'] = $validated['trang_thai'] ?? 'moi_tao';
        }
        if (array_key_exists('nguoi_tham_gia_ids', $validated)) {
            $validated['nguoi_tham_gia_ids'] = collect($validated['nguoi_tham_gia_ids'] ?? [])
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values()
                ->all();
        }
        if (array_key_exists('combos', $validated)) {
            $validated['combos'] = collect($validated['combos'] ?? [])
                ->map(fn ($item) => [
                    'combo_id' => (int) $item['combo_id'],
                    'so_luong' => (int) $item['so_luong'],
                    'thanh_tien' => (int) $item['thanh_tien'],
                    'ghi_chu' => isset($item['ghi_chu']) ? trim((string) $item['ghi_chu']) : null,
                ])
                ->values()
                ->all();
        }
        if (array_key_exists('dich_vu', $validated)) {
            $validated['dich_vu'] = collect($validated['dich_vu'] ?? [])
                ->map(fn ($item) => [
                    'dich_vu_id' => (int) $item['dich_vu_id'],
                    'so_luong' => (int) $item['so_luong'],
                    'thanh_tien' => (int) $item['thanh_tien'],
                    'ghi_chu' => isset($item['ghi_chu']) ? trim((string) $item['ghi_chu']) : null,
                ])
                ->values()
                ->all();
        }
        if (array_key_exists('concepts', $validated)) {
            $validated['concepts'] = collect($validated['concepts'] ?? [])
                ->map(fn ($item) => [
                    'concept_id' => (int) $item['concept_id'],
                ])
                ->values()
                ->all();
        }
        if (array_key_exists('trang_phucs', $validated)) {
            $validated['trang_phucs'] = collect($validated['trang_phucs'] ?? [])
                ->map(fn ($item) => [
                    'trang_phuc_id' => (int) $item['trang_phuc_id'],
                    'ngay_bat_dau' => $item['ngay_bat_dau'] ?? null,
                    'ngay_ket_thuc' => $item['ngay_ket_thuc'] ?? null,
                ])
                ->values()
                ->all();
        }

        return $validated;
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array{0: ?array, 1: ?array, 2: ?array, 3: ?array}
     */
    private function extractNestedPayload(array &$validated): array
    {
        $combos = array_key_exists('combos', $validated) ? $validated['combos'] : null;
        $dichVu = array_key_exists('dich_vu', $validated) ? $validated['dich_vu'] : null;
        $concepts = array_key_exists('concepts', $validated) ? $validated['concepts'] : null;
        $trangPhucs = array_key_exists('trang_phucs', $validated) ? $validated['trang_phucs'] : null;
        unset($validated['combos'], $validated['dich_vu'], $validated['concepts'], $validated['trang_phucs']);

        return [$combos, $dichVu, $concepts, $trangPhucs];
    }

    /**
     * @param  ?array<int, mixed>  $combos
     * @param  ?array<int, mixed>  $dichVu
     * @param  ?array<int, mixed>  $concepts
     * @param  ?array<int, mixed>  $trangPhucs
     */
    private function syncNestedRelations(
        HopDongSuDungDichVu $hopDong,
        ?array $combos,
        ?array $dichVu,
        ?array $concepts,
        ?array $trangPhucs,
    ): void {
        if (is_array($combos)) {
            $this->syncCombos($hopDong, $combos);
        }
        if (is_array($dichVu)) {
            $this->syncDichVu($hopDong, $dichVu);
        }
        if (is_array($concepts)) {
            $this->syncConcepts($hopDong, $concepts);
        }
        if (is_array($trangPhucs)) {
            $this->syncTrangPhucs($hopDong, $trangPhucs);
        }
    }

    /**
     * @param  array<int, array{combo_id: int, so_luong: int, thanh_tien: int, ghi_chu: ?string}>  $items
     */
    private function syncCombos(HopDongSuDungDichVu $hopDong, array $items): void
    {
        $hopDong->combos()->delete();

        foreach ($items as $item) {
            HopDongDongSddvCombo::create([
                'ma_hop_dong_id' => $hopDong->id,
                'combo_id' => $item['combo_id'],
                'so_luong' => $item['so_luong'],
                'thanh_tien' => $item['thanh_tien'],
                'ghi_chu' => $item['ghi_chu'] !== '' ? $item['ghi_chu'] : null,
            ]);
        }
    }

    /**
     * @param  array<int, array{dich_vu_id: int, so_luong: int, thanh_tien: int, ghi_chu: ?string}>  $items
     */
    private function syncDichVu(HopDongSuDungDichVu $hopDong, array $items): void
    {
        $hopDong->dichVu()->delete();

        foreach ($items as $item) {
            HopDongDongSddvDichVu::create([
                'ma_hop_dong_id' => $hopDong->id,
                'dich_vu_id' => $item['dich_vu_id'],
                'so_luong' => $item['so_luong'],
                'thanh_tien' => $item['thanh_tien'],
                'ghi_chu' => $item['ghi_chu'] !== '' ? $item['ghi_chu'] : null,
            ]);
        }
    }

    /**
     * @param  array<int, array{concept_id: int}>  $items
     */
    private function syncConcepts(HopDongSuDungDichVu $hopDong, array $items): void
    {
        $hopDong->concepts()->delete();

        foreach ($items as $item) {
            HopDongDongSddvConcept::create([
                'ma_hop_dong_id' => $hopDong->id,
                'concept_id' => $item['concept_id'],
            ]);
        }
    }

    /**
     * @param  array<int, array{trang_phuc_id: int, ngay_bat_dau: ?string, ngay_ket_thuc: ?string}>  $items
     */
    private function syncTrangPhucs(HopDongSuDungDichVu $hopDong, array $items): void
    {
        $hopDong->trangPhucs()->delete();

        foreach ($items as $item) {
            HopDongDongSddvTrangPhuc::create([
                'ma_hop_dong_id' => $hopDong->id,
                'trang_phuc_id' => $item['trang_phuc_id'],
                'ngay_bat_dau' => $item['ngay_bat_dau'] ?: null,
                'ngay_ket_thuc' => $item['ngay_ket_thuc'] ?: null,
            ]);
        }
    }
}
