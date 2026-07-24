<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TrangPhuc;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class TrangPhucController extends Controller
{
    /**
     * Danh sách trang phục — phân trang + tìm kiếm.
     *
     * Query: page, per_page, keyword, danh_muc, nha_cung_cap, chi_nhanh, trang_thai
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'keyword' => ['sometimes', 'nullable', 'string', 'max:255'],
            'danh_muc' => ['sometimes', 'nullable', 'integer', 'exists:danh_muc_trang_phuc,id'],
            'nha_cung_cap' => ['sometimes', 'nullable', 'integer', 'exists:nha_cung_cap_trang_phuc,id'],
            'chi_nhanh' => ['sometimes', 'nullable', 'integer', 'exists:cau_hinh_chi_nhanh,id'],
            'trang_thai' => ['sometimes', 'nullable', Rule::in([0, 1, '0', '1'])],
        ]);

        $perPage = $validated['per_page'] ?? 10;
        $keyword = trim((string) ($validated['keyword'] ?? ''));

        $query = TrangPhuc::query()
            ->with([
                'danhMucTrangPhuc:id,ten_danh_muc,ma_danh_muc',
                'nhaCungCapTrangPhuc:id,ten_nha_cung_cap,ma_nha_cung_cap',
                'cauHinhChiNhanh:id,ten_chi_nhanh',
            ])
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
            ->orderByDesc('id');

        return response()->json($query->paginate($perPage));
    }

    /**
     * Chi tiết một trang phục.
     */
    public function show(TrangPhuc $trang_phuc): JsonResponse
    {
        $trang_phuc->load([
            'danhMucTrangPhuc:id,ten_danh_muc,ma_danh_muc',
            'nhaCungCapTrangPhuc:id,ten_nha_cung_cap,ma_nha_cung_cap',
            'cauHinhChiNhanh:id,ten_chi_nhanh',
        ]);

        return response()->json($trang_phuc);
    }

    /**
     * Upload hình ảnh trang phục.
     */
    public function uploadHinhAnh(Request $request): JsonResponse
    {
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
    }

    /**
     * Tạo trang phục mới.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $this->validatePayload($request);

        $trangPhuc = TrangPhuc::create($validated);
        $trangPhuc->load([
            'danhMucTrangPhuc:id,ten_danh_muc,ma_danh_muc',
            'nhaCungCapTrangPhuc:id,ten_nha_cung_cap,ma_nha_cung_cap',
            'cauHinhChiNhanh:id,ten_chi_nhanh',
        ]);

        return response()->json($trangPhuc, 201);
    }

    /**
     * Cập nhật trang phục.
     */
    public function update(Request $request, TrangPhuc $trang_phuc): JsonResponse
    {
        $validated = $this->validatePayload($request, $trang_phuc);

        $trang_phuc->update($validated);

        return response()->json($trang_phuc->fresh()->load([
            'danhMucTrangPhuc:id,ten_danh_muc,ma_danh_muc',
            'nhaCungCapTrangPhuc:id,ten_nha_cung_cap,ma_nha_cung_cap',
            'cauHinhChiNhanh:id,ten_chi_nhanh',
        ]));
    }

    /**
     * Xóa trang phục.
     */
    public function destroy(TrangPhuc $trang_phuc): JsonResponse
    {
        if ($trang_phuc->hinh_anh) {
            Storage::disk('public')->delete($trang_phuc->hinh_anh);
        }

        $trang_phuc->delete();

        return response()->json(['message' => 'Đã xóa trang phục.']);
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
}
