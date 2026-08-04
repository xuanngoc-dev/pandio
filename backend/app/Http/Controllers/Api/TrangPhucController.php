<?php

namespace App\Http\Controllers\Api;

use App\Models\TrangPhuc;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class TrangPhucController extends BaseApiController
{
    /**
     * Danh sách trang phục — phân trang + tìm kiếm.
     *
     * Query: page, per_page, keyword, danh_muc, nha_cung_cap, chi_nhanh, trang_thai, gia_tu, gia_den,
     *        ngay_thue, ngay_tra_du_kien, exclude_hop_dong_id
     */
    public function index(Request $request): JsonResponse
    {
        return $this->handleApi(function () use ($request) {
            $validated = $request->validate([
                'page' => ['sometimes', 'integer', 'min:1'],
                'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
                'keyword' => ['sometimes', 'nullable', 'string', 'max:255'],
                'danh_muc' => ['sometimes', 'nullable', 'integer', 'exists:danh_muc_trang_phuc,id'],
                'nha_cung_cap' => ['sometimes', 'nullable', 'integer', 'exists:nha_cung_cap_trang_phuc,id'],
                'chi_nhanh' => ['sometimes', 'nullable', 'integer', 'exists:cau_hinh_chi_nhanh,id'],
                'trang_thai' => ['sometimes', 'nullable', Rule::in([0, 1, '0', '1'])],
                'gia_tu' => ['sometimes', 'nullable', 'integer', 'min:0'],
                'gia_den' => ['sometimes', 'nullable', 'integer', 'min:0'],
                'ngay_thue' => ['sometimes', 'nullable', 'date'],
                'ngay_tra_du_kien' => ['sometimes', 'nullable', 'date', 'after_or_equal:ngay_thue'],
                'exclude_hop_dong_id' => ['sometimes', 'nullable', 'integer', 'exists:hop_dong_cho_thue_trang_phuc,id'],
            ]);

            $perPage = $validated['per_page'] ?? 10;
            $keyword = trim((string) ($validated['keyword'] ?? ''));
            $ngayThue = $validated['ngay_thue'] ?? null;
            $ngayTraDuKien = $validated['ngay_tra_du_kien'] ?? null;
            $excludeHopDongId = isset($validated['exclude_hop_dong_id'])
                ? (int) $validated['exclude_hop_dong_id']
                : null;
            $checkOverlap = filled($ngayThue) && filled($ngayTraDuKien);

            $existsRelations = [
                'lichChoThue as co_lich_cho_thue' => function ($q) {
                    $q->whereNotNull('ngay_bat_dau')
                        ->where(function ($inner) {
                            $inner->whereNotNull('ngay_ket_thuc_du_kien')
                                ->orWhereNotNull('ngay_ket_thuc_thuc_te');
                        })
                        ->whereHas('hopDong', function ($hd) {
                            $hd->whereNotIn('trang_thai', ['moi_tao', 'nhap', 'da_huy']);
                        });
                },
            ];

            if ($checkOverlap) {
                $existsRelations['lichChoThue as dang_su_dung'] = function ($q) use ($ngayThue, $ngayTraDuKien, $excludeHopDongId) {
                    $this->applyLichChoThueOverlapFilter($q, $ngayThue, $ngayTraDuKien, $excludeHopDongId);
                };
            }

            $query = TrangPhuc::query()
                ->with([
                    'danhMucTrangPhuc:id,ten_danh_muc,ma_danh_muc',
                    'nhaCungCapTrangPhuc:id,ten_nha_cung_cap,ma_nha_cung_cap',
                    'cauHinhChiNhanh:id,ten_chi_nhanh',
                ])
                ->withCount('lichChoThue as luot_thue')
                ->withExists($existsRelations)
                ->when($keyword !== '', function ($q) use ($keyword) {
                    $q->where(function ($inner) use ($keyword) {
                        $inner->where('ma_san_pham', 'like', "%{$keyword}%")
                            ->orWhere('ten_san_pham', 'like', "%{$keyword}%")
                            ->orWhere('ghi_chu', 'like', "%{$keyword}%");
                    });
                })
                ->when(isset($validated['danh_muc']), fn ($q) => $q->where('danh_muc', $validated['danh_muc']))
                ->when(isset($validated['nha_cung_cap']), fn ($q) => $q->where('nha_cung_cap', $validated['nha_cung_cap']))
                ->when(isset($validated['chi_nhanh']), fn ($q) => $q->where('chi_nhanh', $validated['chi_nhanh']))
                ->when(array_key_exists('trang_thai', $validated) && $validated['trang_thai'] !== null, function ($q) use ($validated) {
                    $q->where('trang_thai', (int) $validated['trang_thai']);
                })
                ->when(isset($validated['gia_tu']), fn ($q) => $q->where('gia_cho_thue', '>=', (int) $validated['gia_tu']))
                ->when(isset($validated['gia_den']), fn ($q) => $q->where('gia_cho_thue', '<=', (int) $validated['gia_den']))
                ->orderByDesc('id');

            return response()->json($query->paginate($perPage));

        }, 'lấy danh sách trang phục');
    }

    /**
     * Lịch sử cho thuê của một trang phục.
     */
    public function lichChoThue(TrangPhuc $trang_phuc): JsonResponse
    {
        return $this->handleApi(function () use ($trang_phuc) {
            $items = $trang_phuc->lichChoThue()
                ->whereNotNull('ngay_bat_dau')
                ->where(function ($q) {
                    $q->whereNotNull('ngay_ket_thuc_du_kien')
                        ->orWhereNotNull('ngay_ket_thuc_thuc_te');
                })
                ->whereHas('hopDong', function ($hd) {
                    $hd->whereNotIn('trang_thai', ['moi_tao', 'nhap']);
                })
                ->with([
                    'hopDong:id,ma_hop_dong,ten_khach_hang,sdt_khach_hang,trang_thai',
                ])
                ->orderByDesc('ngay_bat_dau')
                ->orderByDesc('id')
                ->get();

            return response()->json($items);

        }, 'lấy lịch cho thuê trang phục');
    }

    /**
     * Chi tiết một trang phục.
     */
    public function show(TrangPhuc $trang_phuc): JsonResponse
    {
        return $this->handleApi(function () use ($trang_phuc) {
            $trang_phuc->load([
                'danhMucTrangPhuc:id,ten_danh_muc,ma_danh_muc',
                'nhaCungCapTrangPhuc:id,ten_nha_cung_cap,ma_nha_cung_cap',
                'cauHinhChiNhanh:id,ten_chi_nhanh',
            ]);

            return response()->json($trang_phuc);

        }, 'lấy chi tiết trang phục');
    }

    /**
     * Upload hình ảnh trang phục.
     */
    public function uploadHinhAnh(Request $request): JsonResponse
    {
        return $this->handleApi(function () use ($request) {
            $validated = $request->validate([
                'hinh_anh' => ['required', 'image', 'mimes:jpeg,jpg,png,webp,gif', 'max:2048'],
            ], [
                'hinh_anh.required' => 'Vui lòng chọn hình ảnh.',
                'hinh_anh.image' => 'File phải là hình ảnh.',
                'hinh_anh.mimes' => 'Chỉ chấp nhận jpeg, jpg, png, webp, gif.',
                'hinh_anh.max' => 'Hình ảnh tối đa 2MB.',
            ]);

            $path = $validated['hinh_anh']->store('trang-phuc', 'public');

            return response()->json([
                'path' => $path,
                'url' => Storage::disk('public')->url($path),
            ], 201);

        }, 'upload hình ảnh trang phục');
    }

    /**
     * Tạo trang phục mới.
     */
    public function store(Request $request): JsonResponse
    {
        return $this->handleApi(function () use ($request) {
            $validated = $this->validatePayload($request);

            $trangPhuc = TrangPhuc::create($validated);
            $trangPhuc->load([
                'danhMucTrangPhuc:id,ten_danh_muc,ma_danh_muc',
                'nhaCungCapTrangPhuc:id,ten_nha_cung_cap,ma_nha_cung_cap',
                'cauHinhChiNhanh:id,ten_chi_nhanh',
            ]);

            return response()->json($trangPhuc, 201);

        }, 'tạo trang phục');
    }

    /**
     * Cập nhật trang phục.
     */
    public function update(Request $request, TrangPhuc $trang_phuc): JsonResponse
    {
        return $this->handleApi(function () use ($request, $trang_phuc) {
            $validated = $this->validatePayload($request, $trang_phuc);

            $trang_phuc->update($validated);

            return response()->json($trang_phuc->fresh()->load([
                'danhMucTrangPhuc:id,ten_danh_muc,ma_danh_muc',
                'nhaCungCapTrangPhuc:id,ten_nha_cung_cap,ma_nha_cung_cap',
                'cauHinhChiNhanh:id,ten_chi_nhanh',
            ]));

        }, 'cập nhật trang phục');
    }

    /**
     * Xóa trang phục.
     */
    public function destroy(TrangPhuc $trang_phuc): JsonResponse
    {
        return $this->handleApi(function () use ($trang_phuc) {
            if ($trang_phuc->hinh_anh) {
                Storage::disk('public')->delete($trang_phuc->hinh_anh);
            }

            $trang_phuc->delete();

            return response()->json(['message' => 'Đã xóa trang phục.']);

        }, 'xóa trang phục');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatePayload(Request $request, ?TrangPhuc $trangPhuc = null): array
    {
        $validated = $request->validate([
            'hinh_anh' => ['nullable', 'string', 'max:500'],
            'ma_san_pham' => [
                'required',
                'string',
                'max:50',
                Rule::unique('trang_phuc', 'ma_san_pham')->ignore($trangPhuc?->id),
            ],
            'ten_san_pham' => ['required', 'string', 'max:255'],
            'danh_muc' => ['required', 'integer', 'exists:danh_muc_trang_phuc,id'],
            'nha_cung_cap' => ['required', 'integer', 'exists:nha_cung_cap_trang_phuc,id'],
            'chi_nhanh' => ['required', 'integer', 'exists:cau_hinh_chi_nhanh,id'],
            'gia_tri' => ['required', 'integer', 'min:0'],
            'gia_cho_thue' => ['required', 'integer', 'min:0'],
            'phan_loai_chi_phi' => ['required', 'string', Rule::in(['dau_tu_tai_san', 'vat_tu_tieu_hao'])],
            'tinh_trang' => ['required', 'string', Rule::in(['con_hang', 'dang_cho_thue', 'dang_sua_chua', 'ngung_su_dung'])],
            'ghi_chu' => ['nullable', 'string'],
            'trang_thai' => ['required', Rule::in([0, 1, '0', '1'])],
            'thong_tin_them' => ['nullable', 'array'],
            'thong_tin_them.*.ten_thuoc_tinh' => ['required', 'string', 'max:255'],
            'thong_tin_them.*.gia_tri' => ['nullable', 'string', 'max:255'],
            'thong_tin_them.*.ghi_chu' => ['nullable', 'string'],
        ]);

        $validated['trang_thai'] = (int) $validated['trang_thai'];

        if (! empty($validated['thong_tin_them'])) {
            $validated['thong_tin_them'] = collect($validated['thong_tin_them'])
                ->map(function (array $item) {
                    return [
                        'ten_thuoc_tinh' => trim((string) $item['ten_thuoc_tinh']),
                        'gia_tri' => trim((string) ($item['gia_tri'] ?? '')) ?: null,
                        'ghi_chu' => trim((string) ($item['ghi_chu'] ?? '')) ?: null,
                    ];
                })
                ->values()
                ->all();
        }

        return $validated;
    }

    /**
     * Lọc lịch cho thuê giao với khoảng [ngay_thue, ngay_tra_du_kien].
     */
    private function applyLichChoThueOverlapFilter(
        Builder $query,
        string $ngayThue,
        string $ngayTraDuKien,
        ?int $excludeHopDongId = null,
    ): void {
        $query->whereNotNull('ngay_bat_dau')
            ->where(function ($inner) {
                $inner->whereNotNull('ngay_ket_thuc_du_kien')
                    ->orWhereNotNull('ngay_ket_thuc_thuc_te');
            })
            ->whereDate('ngay_bat_dau', '<=', $ngayTraDuKien)
            ->whereRaw('COALESCE(ngay_ket_thuc_thuc_te, ngay_ket_thuc_du_kien) >= ?', [$ngayThue])
            ->whereHas('hopDong', function ($hd) use ($excludeHopDongId) {
                $hd->whereNotIn('trang_thai', ['moi_tao', 'nhap', 'da_huy']);
                if ($excludeHopDongId) {
                    $hd->where('id', '!=', $excludeHopDongId);
                }
            });
    }
}
