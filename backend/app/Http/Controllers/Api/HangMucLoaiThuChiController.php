<?php

namespace App\Http\Controllers\Api;

use App\Models\HangMucLoaiThuChi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class HangMucLoaiThuChiController extends BaseApiController
{
    /**
     * Danh sách hạng mục loại thu chi — phân trang + tìm kiếm.
     *
     * Query: page, per_page, keyword, trang_thai
     */
    public function index(Request $request): JsonResponse
    {
        return $this->handleApi(function () use ($request) {
            $validated = $request->validate([
                'page' => ['sometimes', 'integer', 'min:1'],
                'per_page' => ['sometimes', 'integer', 'min:1', 'max:200'],
                'keyword' => ['sometimes', 'nullable', 'string', 'max:255'],
                'trang_thai' => ['sometimes', 'nullable', 'string', 'max:50'],
            ]);

            $perPage = $validated['per_page'] ?? 10;
            $keyword = trim((string) ($validated['keyword'] ?? ''));
            $trangThai = $validated['trang_thai'] ?? null;

            $query = HangMucLoaiThuChi::query()
                ->when($keyword !== '', function ($q) use ($keyword) {
                    $q->where(function ($inner) use ($keyword) {
                        $inner->where('ten_hang_muc', 'like', "%{$keyword}%")
                            ->orWhere('ghi_chu', 'like', "%{$keyword}%");
                    });
                })
                ->when($trangThai, fn ($q) => $q->where('trang_thai', $trangThai))
                ->orderByDesc('id');

            return response()->json($query->paginate($perPage));

        }, 'lấy danh sách hạng mục loại thu chi');
    }

    public function show(HangMucLoaiThuChi $hang_muc_loai_thu_chi): JsonResponse
    {
        return $this->handleApi(function () use ($hang_muc_loai_thu_chi) {
            return response()->json($hang_muc_loai_thu_chi);

        }, 'lấy chi tiết hạng mục loại thu chi');
    }

    public function store(Request $request): JsonResponse
    {
        return $this->handleApi(function () use ($request) {
            $validated = $request->validate([
                'ten_hang_muc' => ['required', 'string', 'max:255'],
                'ghi_chu' => ['nullable', 'string'],
                'trang_thai' => ['required', Rule::in(['hoat_dong', 'ngung_hoat_dong'])],
            ]);

            $item = HangMucLoaiThuChi::create($validated);

            return response()->json($item, 201);

        }, 'tạo hạng mục loại thu chi');
    }

    public function update(Request $request, HangMucLoaiThuChi $hang_muc_loai_thu_chi): JsonResponse
    {
        return $this->handleApi(function () use ($request, $hang_muc_loai_thu_chi) {
            $validated = $request->validate([
                'ten_hang_muc' => ['required', 'string', 'max:255'],
                'ghi_chu' => ['nullable', 'string'],
                'trang_thai' => ['required', Rule::in(['hoat_dong', 'ngung_hoat_dong'])],
            ]);

            $hang_muc_loai_thu_chi->update($validated);

            return response()->json($hang_muc_loai_thu_chi->fresh());

        }, 'cập nhật hạng mục loại thu chi');
    }

    public function destroy(HangMucLoaiThuChi $hang_muc_loai_thu_chi): JsonResponse
    {
        return $this->handleApi(function () use ($hang_muc_loai_thu_chi) {
            $hang_muc_loai_thu_chi->delete();

            return response()->json(['message' => 'Đã xóa hạng mục loại thu chi.']);

        }, 'xóa hạng mục loại thu chi');
    }
}
