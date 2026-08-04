<?php

namespace App\Http\Controllers\Api;

use App\Models\HopDongChoThueTrangPhuc;
use App\Models\HopDongChoThueTrangPhucSanPhamChoThue;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class HopDongChoThueTrangPhucController extends BaseApiController
{
    /**
     * Danh sách hợp đồng cho thuê trang phục — phân trang + tìm kiếm.
     *
     * Query: page, per_page, keyword, trang_thai, chi_nhap, tu_ngay, den_ngay
     */
    public function index(Request $request): JsonResponse
    {
        return $this->handleApi(function () use ($request) {
            $validated = $request->validate([
                'page' => ['sometimes', 'integer', 'min:1'],
                'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
                'keyword' => ['sometimes', 'nullable', 'string', 'max:255'],
                'trang_thai' => ['sometimes', 'nullable', 'string', 'max:50'],
                'chi_nhap' => ['sometimes', 'boolean'],
                'tu_ngay' => ['sometimes', 'nullable', 'date'],
                'den_ngay' => ['sometimes', 'nullable', 'date', 'after_or_equal:tu_ngay'],
            ]);

            $perPage = $validated['per_page'] ?? 10;
            $keyword = trim((string) ($validated['keyword'] ?? ''));
            $trangThai = $validated['trang_thai'] ?? null;
            $chiNhap = $request->boolean('chi_nhap');
            $tuNgay = $validated['tu_ngay'] ?? null;
            $denNgay = $validated['den_ngay'] ?? null;

            $query = HopDongChoThueTrangPhuc::query()
                ->with([
                    'nguoiChoThueUser:id,name,phone',
                    'sanPhamChoThue.sanPham:id,ma_san_pham,ten_san_pham,gia_tri,gia_cho_thue,hinh_anh',
                ])
                ->when($keyword !== '', function ($q) use ($keyword) {
                    $q->where(function ($inner) use ($keyword) {
                        $inner->where('ma_hop_dong', 'like', "%{$keyword}%")
                            ->orWhere('ten_khach_hang', 'like', "%{$keyword}%")
                            ->orWhere('sdt_khach_hang', 'like', "%{$keyword}%");
                    });
                })
                ->when($tuNgay, fn ($q) => $q->whereDate('created_at', '>=', $tuNgay))
                ->when($denNgay, fn ($q) => $q->whereDate('created_at', '<=', $denNgay))
                ->when($chiNhap, function ($q) {
                    $q->whereIn('trang_thai', ['moi_tao', 'nhap']);
                }, function ($q) use ($trangThai) {
                    if ($trangThai) {
                        $q->where('trang_thai', $trangThai);
                    } else {
                        $q->whereNotIn('trang_thai', ['moi_tao', 'nhap']);
                    }
                })
                ->orderByDesc('ngay_thue')
                ->orderByDesc('id');

            return response()->json($query->paginate($perPage));

        }, 'lấy danh sách hợp đồng cho thuê trang phục');
    }

    /**
     * Chi tiết một hợp đồng cho thuê trang phục.
     */
    public function show(HopDongChoThueTrangPhuc $hop_dong_cho_thue_trang_phuc): JsonResponse
    {
        return $this->handleApi(function () use ($hop_dong_cho_thue_trang_phuc) {
            $hop_dong_cho_thue_trang_phuc->load([
                'nguoiChoThueUser:id,name,phone',
                'sanPhamChoThue.sanPham:id,ma_san_pham,ten_san_pham,gia_tri,gia_cho_thue,hinh_anh',
            ]);

            return response()->json($hop_dong_cho_thue_trang_phuc);

        }, 'lấy chi tiết hợp đồng cho thuê trang phục');
    }

    /**
     * Khởi tạo hợp đồng nháp + sinh mã HDTTP_DDMMYYYY{id}.
     */
    public function khoiTao(Request $request): JsonResponse
    {
        return $this->handleApi(function () use ($request) {
            $hopDong = DB::transaction(function () use ($request) {
                $hopDong = HopDongChoThueTrangPhuc::create([
                    'ma_hop_dong' => 'TEMP_'.Str::upper(Str::random(16)),
                    'ten_khach_hang' => null,
                    'sdt_khach_hang' => null,
                    'ngay_thue' => null,
                    'ngay_tra_du_kien' => null,
                    'ngay_tra_chinh_thuc' => null,
                    'so_ngay_thue' => 1,
                    'tong_tien' => 0,
                    'giam_gia' => 0,
                    'thanh_tien' => 0,
                    'tien_coc' => 0,
                    'trang_thai' => 'moi_tao',
                    'nguoi_cho_thue' => $request->user()->id,
                    'nguoi_tham_gia' => [],
                    'ghi_chu_sale' => null,
                    'ghi_chu_khach' => null,
                ]);

                $hopDong->update([
                    'ma_hop_dong' => HopDongChoThueTrangPhuc::buildMaHopDong($hopDong->id),
                ]);

                return $hopDong;
            });

            $hopDong->load([
                'nguoiChoThueUser:id,name,phone',
                'sanPhamChoThue.sanPham:id,ma_san_pham,ten_san_pham,gia_tri,gia_cho_thue,hinh_anh',
            ]);

            return response()->json($hopDong, 201);

        }, 'khởi tạo hợp đồng cho thuê trang phục');
    }

    /**
     * Tạo hợp đồng cho thuê trang phục mới.
     */
    public function store(Request $request): JsonResponse
    {
        return $this->handleApi(function () use ($request) {
            $validated = $this->validatePayload($request);
            $validated = $this->applyCalculatedFields($validated);
            $validated['nguoi_cho_thue'] = $request->user()->id;
            $sanPhamItems = $validated['san_pham_cho_thue'] ?? [];
            unset($validated['san_pham_cho_thue']);

            $hopDong = DB::transaction(function () use ($validated, $sanPhamItems) {
                $hopDong = HopDongChoThueTrangPhuc::create($validated);
                $this->syncSanPhamChoThue(
                    $hopDong,
                    $sanPhamItems,
                    $this->shouldPersistSanPhamDates($validated['trang_thai'] ?? null),
                );

                return $hopDong;
            });

            $hopDong->load([
                'nguoiChoThueUser:id,name,phone',
                'sanPhamChoThue.sanPham:id,ma_san_pham,ten_san_pham,gia_tri,gia_cho_thue,hinh_anh',
            ]);

            return response()->json($hopDong, 201);

        }, 'tạo hợp đồng cho thuê trang phục');
    }

    /**
     * Cập nhật hợp đồng cho thuê trang phục.
     */
    public function update(Request $request, HopDongChoThueTrangPhuc $hop_dong_cho_thue_trang_phuc): JsonResponse
    {
        return $this->handleApi(function () use ($request, $hop_dong_cho_thue_trang_phuc) {
            $validated = $this->validatePayload($request, $hop_dong_cho_thue_trang_phuc->id, true);
            $validated = $this->applyCalculatedFields($validated, true);
            unset($validated['nguoi_cho_thue'], $validated['ma_hop_dong']);
            $sanPhamItems = $validated['san_pham_cho_thue'] ?? null;
            unset($validated['san_pham_cho_thue']);

            DB::transaction(function () use ($hop_dong_cho_thue_trang_phuc, $validated, $sanPhamItems) {
                if ($validated !== []) {
                    $hop_dong_cho_thue_trang_phuc->update($validated);
                }
                if ($sanPhamItems !== null) {
                    $trangThai = $validated['trang_thai'] ?? $hop_dong_cho_thue_trang_phuc->trang_thai;
                    $this->syncSanPhamChoThue(
                        $hop_dong_cho_thue_trang_phuc->fresh(),
                        $sanPhamItems,
                        $this->shouldPersistSanPhamDates($trangThai),
                    );
                }
            });

            return response()->json($hop_dong_cho_thue_trang_phuc->fresh()->load([
                'nguoiChoThueUser:id,name,phone',
                'sanPhamChoThue.sanPham:id,ma_san_pham,ten_san_pham,gia_tri,gia_cho_thue,hinh_anh',
            ]));

        }, 'cập nhật hợp đồng cho thuê trang phục');
    }

    /**
     * Xóa hợp đồng cho thuê trang phục.
     */
    public function destroy(HopDongChoThueTrangPhuc $hop_dong_cho_thue_trang_phuc): JsonResponse
    {
        return $this->handleApi(function () use ($hop_dong_cho_thue_trang_phuc) {
            $hop_dong_cho_thue_trang_phuc->delete();

            return response()->json(['message' => 'Đã xóa hợp đồng cho thuê trang phục.']);

        }, 'xóa hợp đồng cho thuê trang phục');
    }

    /**
     * Thanh toán hợp đồng cho thuê trang phục.
     */
    public function thanhToan(Request $request, HopDongChoThueTrangPhuc $hop_dong_cho_thue_trang_phuc): JsonResponse
    {
        return $this->handleApi(function () use ($request, $hop_dong_cho_thue_trang_phuc) {
            $validated = $request->validate([
                'so_tien_thanh_toan' => ['required', 'integer', 'min:0'],
                'hinh_thuc_thanh_toan' => [
                    'nullable',
                    'string',
                    Rule::in(['tien_mat', 'chuyen_khoan']),
                    Rule::requiredIf(fn () => (int) $request->input('so_tien_thanh_toan', 0) > 0
                        || (int) $request->input('tien_den_bu', 0) > 0
                        || (int) $request->input('phi_tra_muon', 0) > 0
                        || (int) $request->input('phi_phu_thu', 0) > 0),
                ],
                'phi_tra_muon' => ['nullable', 'integer', 'min:0'],
                'tien_den_bu' => ['nullable', 'integer', 'min:0'],
                'phi_phu_thu' => ['nullable', 'integer', 'min:0'],
                'uu_dai_tat_toan' => ['nullable', 'integer', 'min:0'],
                'tong_tien_thanh_toan' => ['nullable', 'integer', 'min:0'],
                'ngay_tra_chinh_thuc' => ['nullable', 'date'],
                'ghi_chu_sale' => ['nullable', 'string'],
                'ghi_chu_khach' => ['nullable', 'string'],
                'san_pham_cho_thue' => ['sometimes', 'array'],
                'san_pham_cho_thue.*.id' => [
                    'required',
                    'integer',
                    Rule::exists('hop_dong_cho_thue_trang_phuc_san_pham_cho_thue', 'id')
                        ->where('hop_dong_id', $hop_dong_cho_thue_trang_phuc->id),
                ],
                'san_pham_cho_thue.*.trang_thai_hoan_tra' => [
                    'required',
                    'string',
                    Rule::in(['da_hoan_tra', 'chua_hoan_tra']),
                ],
                'san_pham_cho_thue.*.ghi_chu' => ['nullable', 'string'],
            ]);

            $thanhTien = (int) ($hop_dong_cho_thue_trang_phuc->thanh_tien
                ?: max(0, (int) $hop_dong_cho_thue_trang_phuc->tong_tien - (int) $hop_dong_cho_thue_trang_phuc->giam_gia));
            $tienCocHienTai = (int) $hop_dong_cho_thue_trang_phuc->tien_coc;
            $conLai = max(0, $thanhTien - $tienCocHienTai);
            $soTienThanhToan = (int) $validated['so_tien_thanh_toan'];
            $phiTraMuon = (int) ($validated['phi_tra_muon'] ?? 0);
            $tienDenBu = (int) ($validated['tien_den_bu'] ?? 0);
            $phiPhuThu = (int) ($validated['phi_phu_thu'] ?? 0);
            $uuDaiTatToan = (int) ($validated['uu_dai_tat_toan'] ?? 0);
            $tongTienThanhToan = array_key_exists('tong_tien_thanh_toan', $validated)
                ? (int) $validated['tong_tien_thanh_toan']
                : max(0, $soTienThanhToan + $phiTraMuon + $tienDenBu + $phiPhuThu - $uuDaiTatToan);

            if ($soTienThanhToan > $conLai) {
                return response()->json([
                    'message' => 'Số tiền thanh toán không được vượt quá số tiền còn lại.',
                    'errors' => [
                        'so_tien_thanh_toan' => ['Số tiền thanh toán không được vượt quá số tiền còn lại.'],
                    ],
                ], 422);
            }

            DB::transaction(function () use ($hop_dong_cho_thue_trang_phuc, $validated, $tienCocHienTai, $soTienThanhToan, $phiTraMuon, $tienDenBu, $phiPhuThu, $uuDaiTatToan, $tongTienThanhToan) {
                $updateData = [
                    'phi_tra_muon' => $phiTraMuon,
                    'tien_den_bu' => $tienDenBu,
                    'phi_phu_thu' => $phiPhuThu,
                    'uu_dai_tat_toan' => $uuDaiTatToan,
                    'tong_tien_thanh_toan' => $tongTienThanhToan,
                    'ngay_tra_chinh_thuc' => $validated['ngay_tra_chinh_thuc'] ?? now()->toDateString(),
                    'ghi_chu_sale' => $validated['ghi_chu_sale'] ?? null,
                    'ghi_chu_khach' => $validated['ghi_chu_khach'] ?? null,
                ];
                if ($soTienThanhToan > 0) {
                    $updateData['tien_coc'] = $tienCocHienTai + $soTienThanhToan;
                }
                if ($soTienThanhToan > 0 || $phiTraMuon > 0 || $tienDenBu > 0 || $phiPhuThu > 0) {
                    $updateData['hinh_thuc_thanh_toan'] = $validated['hinh_thuc_thanh_toan'] ?? null;
                }
                $hop_dong_cho_thue_trang_phuc->update($updateData);

                foreach ($validated['san_pham_cho_thue'] ?? [] as $item) {
                    HopDongChoThueTrangPhucSanPhamChoThue::query()
                        ->where('hop_dong_id', $hop_dong_cho_thue_trang_phuc->id)
                        ->where('id', $item['id'])
                        ->update([
                            'trang_thai_hoan_tra' => $item['trang_thai_hoan_tra'],
                            'ghi_chu' => $item['ghi_chu'] ?? null,
                        ]);
                }
            });

            return response()->json($hop_dong_cho_thue_trang_phuc->fresh()->load([
                'nguoiChoThueUser:id,name,phone',
                'sanPhamChoThue.sanPham:id,ma_san_pham,ten_san_pham,gia_tri,gia_cho_thue,hinh_anh',
            ]));

        }, 'thanh toán hợp đồng cho thuê trang phục');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatePayload(Request $request, ?int $ignoreId = null, bool $isUpdate = false): array
    {
        $maHopDongRule = Rule::unique('hop_dong_cho_thue_trang_phuc', 'ma_hop_dong');
        if ($ignoreId !== null) {
            $maHopDongRule = $maHopDongRule->ignore($ignoreId);
        }

        $trangThai = $request->input('trang_thai');
        $isDraft = in_array($trangThai, ['moi_tao', 'nhap'], true);
        $required = $isDraft ? 'nullable' : 'required';

        return $request->validate([
            'ma_hop_dong' => [$isUpdate ? 'sometimes' : 'required', 'string', 'max:100', $maHopDongRule],
            'ten_khach_hang' => [$required, 'nullable', 'string', 'max:255'],
            'sdt_khach_hang' => [$required, 'nullable', 'string', 'max:20'],
            'ngay_thue' => [$required, 'nullable', 'date'],
            'ngay_tra_du_kien' => [
                $required,
                'nullable',
                'date',
                Rule::when(
                    filled($request->input('ngay_thue')) && filled($request->input('ngay_tra_du_kien')),
                    ['after_or_equal:ngay_thue']
                ),
            ],
            'ngay_tra_chinh_thuc' => [
                'nullable',
                'date',
                Rule::when(
                    filled($request->input('ngay_thue')) && filled($request->input('ngay_tra_chinh_thuc')),
                    ['after_or_equal:ngay_thue']
                ),
            ],
            'tong_tien' => ['nullable', 'integer', 'min:0'],
            'giam_gia' => ['nullable', 'integer', 'min:0'],
            'thanh_tien' => ['nullable', 'integer', 'min:0'],
            'tien_coc' => ['nullable', 'integer', 'min:0'],
            'hinh_thuc_thanh_toan' => ['nullable', 'string', Rule::in(['tien_mat', 'chuyen_khoan'])],
            'trang_thai' => ['required', 'string', Rule::in([
                'moi_tao',
                'nhap',
                'cho_xac_nhan',
                'dang_thue',
                'da_tra',
                'qua_han',
                'hoan_thanh',
                'da_huy',
            ])],
            'nguoi_cho_thue' => ['nullable', 'integer', 'exists:users,id'],
            'nguoi_tham_gia' => ['nullable', 'array'],
            'nguoi_tham_gia.*' => ['integer', 'exists:users,id'],
            'ghi_chu_sale' => ['nullable', 'string'],
            'ghi_chu_khach' => ['nullable', 'string'],
            'san_pham_cho_thue' => [
                $isUpdate ? 'sometimes' : ($isDraft ? 'nullable' : 'required'),
                'array',
                $isDraft ? 'nullable' : 'min:1',
            ],
            'san_pham_cho_thue.*.san_pham_id' => ['required', 'integer', 'exists:trang_phuc,id', 'distinct'],
            'san_pham_cho_thue.*.ghi_chu' => ['nullable', 'string'],
            'san_pham_cho_thue.*.trang_thai_hoan_tra' => [
                'nullable',
                'string',
                Rule::in(['da_hoan_tra', 'chua_hoan_tra']),
            ],
        ]);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function applyCalculatedFields(array $validated, bool $isUpdate = false): array
    {
        if (! empty($validated['ngay_thue']) && ! empty($validated['ngay_tra_du_kien'])) {
            $ngayThue = Carbon::parse($validated['ngay_thue'])->startOfDay();
            $ngayTraDuKien = Carbon::parse($validated['ngay_tra_du_kien'])->startOfDay();
            $validated['so_ngay_thue'] = max(1, $ngayThue->diffInDays($ngayTraDuKien) + 1);
        } elseif (! $isUpdate) {
            $validated['so_ngay_thue'] = 1;
        }

        if (array_key_exists('tong_tien', $validated)) {
            $validated['tong_tien'] = (int) ($validated['tong_tien'] ?? 0);
        }
        if (array_key_exists('giam_gia', $validated)) {
            $validated['giam_gia'] = (int) ($validated['giam_gia'] ?? 0);
        }
        if (array_key_exists('thanh_tien', $validated)) {
            $validated['thanh_tien'] = (int) ($validated['thanh_tien'] ?? 0);
        }
        if (array_key_exists('tien_coc', $validated)) {
            $validated['tien_coc'] = (int) ($validated['tien_coc'] ?? 0);
        }

        if (array_key_exists('nguoi_tham_gia', $validated)) {
            $validated['nguoi_tham_gia'] = collect($validated['nguoi_tham_gia'] ?? [])
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values()
                ->all();
        }

        if (array_key_exists('san_pham_cho_thue', $validated)) {
            $validated['san_pham_cho_thue'] = collect($validated['san_pham_cho_thue'] ?? [])
                ->map(fn (array $item) => [
                    'san_pham_id' => (int) $item['san_pham_id'],
                    'ghi_chu' => isset($item['ghi_chu']) ? trim((string) $item['ghi_chu']) : null,
                    'trang_thai_hoan_tra' => in_array($item['trang_thai_hoan_tra'] ?? null, ['da_hoan_tra', 'chua_hoan_tra'], true)
                        ? $item['trang_thai_hoan_tra']
                        : null,
                ])
                ->values()
                ->all();
        }

        return $validated;
    }

    private function shouldPersistSanPhamDates(?string $trangThai): bool
    {
        return ! in_array($trangThai, ['moi_tao', 'nhap'], true);
    }

    /**
     * @param  array<int, array{san_pham_id: int, ghi_chu: ?string, trang_thai_hoan_tra?: ?string}>  $sanPhamItems
     */
    private function syncSanPhamChoThue(
        HopDongChoThueTrangPhuc $hopDong,
        array $sanPhamItems,
        bool $persistDates = false,
    ): void {
        $existingBySanPhamId = $hopDong->sanPhamChoThue()
            ->get(['san_pham_id', 'trang_thai_hoan_tra'])
            ->keyBy('san_pham_id');

        $hopDong->sanPhamChoThue()->delete();

        $ngayBatDau = $persistDates ? ($hopDong->ngay_thue?->format('Y-m-d') ?: null) : null;
        $ngayKetThucDuKien = $persistDates ? ($hopDong->ngay_tra_du_kien?->format('Y-m-d') ?: null) : null;
        $ngayKetThucThucTe = $persistDates
            ? ($hopDong->ngay_tra_chinh_thuc?->format('Y-m-d') ?: null)
            : null;

        foreach ($sanPhamItems as $item) {
            $trangThaiHoanTra = $item['trang_thai_hoan_tra']
                ?? $existingBySanPhamId->get($item['san_pham_id'])?->trang_thai_hoan_tra
                ?? 'chua_hoan_tra';

            HopDongChoThueTrangPhucSanPhamChoThue::create([
                'hop_dong_id' => $hopDong->id,
                'san_pham_id' => $item['san_pham_id'],
                'ngay_bat_dau' => $ngayBatDau,
                'ngay_ket_thuc_du_kien' => $ngayKetThucDuKien,
                'ngay_ket_thuc_thuc_te' => $ngayKetThucThucTe,
                'ghi_chu' => $item['ghi_chu'] ?: null,
                'trang_thai_hoan_tra' => $trangThaiHoanTra,
            ]);
        }
    }
}
