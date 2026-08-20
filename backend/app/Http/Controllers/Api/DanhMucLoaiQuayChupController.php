<?php

namespace App\Http\Controllers\Api;

use App\Models\DanhMucLoaiQuayChup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DanhMucLoaiQuayChupController extends BaseApiController
{
    /**
     * Danh sách loại quay chụp — phân trang + tìm kiếm.
     *
     * Query: page, per_page, keyword, trang_thai
     */
    public function index(Request $request): JsonResponse
    {
        return $this->handleApi(function () use ($request) {
            $validated = $request->validate([
                'page' => ['sometimes', 'integer', 'min:1'],
                'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
                'keyword' => ['sometimes', 'nullable', 'string', 'max:255'],
                'trang_thai' => ['sometimes', 'nullable', 'string', Rule::in(['active', 'inactive'])],
            ]);

            $perPage = $validated['per_page'] ?? 10;
            $keyword = trim((string) ($validated['keyword'] ?? ''));
            $trangThai = $validated['trang_thai'] ?? null;

            $query = DanhMucLoaiQuayChup::query()
                ->when($keyword !== '', function ($q) use ($keyword) {
                    $q->where(function ($inner) use ($keyword) {
                        $inner->where('ten_dich_vu', 'like', "%{$keyword}%")
                            ->orWhere('ghi_chu', 'like', "%{$keyword}%");
                    });
                })
                ->when($trangThai, fn ($q) => $q->where('trang_thai', $trangThai))
                ->orderByDesc('id');

            return response()->json($query->paginate($perPage));

        }, 'lấy danh sách loại quay chụp');
    }

    /**
     * Chi tiết một loại quay chụp.
     */
    public function show(DanhMucLoaiQuayChup $danh_muc_loai_quay_chup): JsonResponse
    {
        return $this->handleApi(function () use ($danh_muc_loai_quay_chup) {
            return response()->json($danh_muc_loai_quay_chup);

        }, 'lấy chi tiết loại quay chụp');
    }

    /**
     * Tạo loại quay chụp mới.
     */
    public function store(Request $request): JsonResponse
    {
        return $this->handleApi(function () use ($request) {
            $validated = $request->validate([
                'ten_dich_vu' => ['required', 'string', 'max:255'],
                'ghi_chu' => ['nullable', 'string'],
                'trang_thai' => ['required', 'string', Rule::in(['active', 'inactive'])],
            ]);

            $item = DanhMucLoaiQuayChup::create($validated);

            return response()->json($item, 201);

        }, 'tạo loại quay chụp');
    }

    /**
     * Cập nhật loại quay chụp.
     */
    public function update(Request $request, DanhMucLoaiQuayChup $danh_muc_loai_quay_chup): JsonResponse
    {
        return $this->handleApi(function () use ($request, $danh_muc_loai_quay_chup) {
            $validated = $request->validate([
                'ten_dich_vu' => ['required', 'string', 'max:255'],
                'ghi_chu' => ['nullable', 'string'],
                'trang_thai' => ['required', 'string', Rule::in(['active', 'inactive'])],
            ]);

            $danh_muc_loai_quay_chup->update($validated);

            return response()->json($danh_muc_loai_quay_chup->fresh());

        }, 'cập nhật loại quay chụp');
    }

    /**
     * Xóa loại quay chụp.
     */
    public function destroy(DanhMucLoaiQuayChup $danh_muc_loai_quay_chup): JsonResponse
    {
        return $this->handleApi(function () use ($danh_muc_loai_quay_chup) {
            $danh_muc_loai_quay_chup->delete();

            return response()->json(['message' => 'Đã xóa loại quay chụp.']);

        }, 'xóa loại quay chụp');
    }
}
