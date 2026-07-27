<?php

namespace App\Http\Controllers\Api;

use App\Models\DanhMucTrangPhuc;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DanhMucTrangPhucController extends BaseApiController
{
    /**
     * Danh sách danh mục trang phục — phân trang + tìm kiếm.
     *
     * Query: page, per_page, keyword
     */
    public function index(Request $request): JsonResponse
    {
        return $this->handleApi(function () use ($request) {
            $validated = $request->validate([
                'page' => ['sometimes', 'integer', 'min:1'],
                'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
                'keyword' => ['sometimes', 'nullable', 'string', 'max:255'],
            ]);

            $perPage = $validated['per_page'] ?? 10;
            $keyword = trim((string) ($validated['keyword'] ?? ''));

            $query = DanhMucTrangPhuc::query()
                ->when($keyword !== '', function ($q) use ($keyword) {
                    $q->where(function ($inner) use ($keyword) {
                        $inner->where('ten_danh_muc', 'like', "%{$keyword}%")
                            ->orWhere('ma_danh_muc', 'like', "%{$keyword}%")
                            ->orWhere('mo_ta', 'like', "%{$keyword}%");
                    });
                })
                ->orderByDesc('id');

            return response()->json($query->paginate($perPage));

        }, 'lấy danh sách danh mục trang phục');
    }

    /**
     * Chi tiết một danh mục trang phục.
     */
    public function show(DanhMucTrangPhuc $danh_muc_trang_phuc): JsonResponse
    {
        return $this->handleApi(function () use ($danh_muc_trang_phuc) {
            return response()->json($danh_muc_trang_phuc);

        }, 'lấy chi tiết danh mục trang phục');
    }

    /**
     * Tạo danh mục trang phục mới.
     */
    public function store(Request $request): JsonResponse
    {
        return $this->handleApi(function () use ($request) {
            $validated = $request->validate([
                'ten_danh_muc' => ['required', 'string', 'max:255'],
                'ma_danh_muc' => ['required', 'string', 'max:50', 'unique:danh_muc_trang_phuc,ma_danh_muc'],
                'mo_ta' => ['nullable', 'string'],
            ]);

            $danhMuc = DanhMucTrangPhuc::create($validated);

            return response()->json($danhMuc, 201);

        }, 'tạo danh mục trang phục');
    }

    /**
     * Cập nhật danh mục trang phục.
     */
    public function update(Request $request, DanhMucTrangPhuc $danh_muc_trang_phuc): JsonResponse
    {
        return $this->handleApi(function () use ($request, $danh_muc_trang_phuc) {
            $validated = $request->validate([
                'ten_danh_muc' => ['required', 'string', 'max:255'],
                'ma_danh_muc' => [
                    'required',
                    'string',
                    'max:50',
                    Rule::unique('danh_muc_trang_phuc', 'ma_danh_muc')->ignore($danh_muc_trang_phuc->id),
                ],
                'mo_ta' => ['nullable', 'string'],
            ]);

            $danh_muc_trang_phuc->update($validated);

            return response()->json($danh_muc_trang_phuc->fresh());

        }, 'cập nhật danh mục trang phục');
    }

    /**
     * Xóa danh mục trang phục.
     */
    public function destroy(DanhMucTrangPhuc $danh_muc_trang_phuc): JsonResponse
    {
        return $this->handleApi(function () use ($danh_muc_trang_phuc) {
            $danh_muc_trang_phuc->delete();

            return response()->json(['message' => 'Đã xóa danh mục trang phục.']);

        }, 'xóa danh mục trang phục');
    }
}
