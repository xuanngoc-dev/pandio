<?php

namespace App\Http\Controllers\Api;

use App\Models\DanhMucConcept;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DanhMucConceptController extends BaseApiController
{
    /**
     * Danh sách danh mục concept — phân trang + tìm kiếm.
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

            $query = DanhMucConcept::query()
                ->when($keyword !== '', function ($q) use ($keyword) {
                    $q->where(function ($inner) use ($keyword) {
                        $inner->where('ten_danh_muc', 'like', "%{$keyword}%")
                            ->orWhere('mo_ta', 'like', "%{$keyword}%");
                    });
                })
                ->orderByDesc('id');

            return response()->json($query->paginate($perPage));

        }, 'lấy danh sách danh mục concept');
    }

    /**
     * Chi tiết một danh mục concept.
     */
    public function show(DanhMucConcept $danh_muc_concept): JsonResponse
    {
        return $this->handleApi(function () use ($danh_muc_concept) {
            return response()->json($danh_muc_concept);

        }, 'lấy chi tiết danh mục concept');
    }

    /**
     * Tạo danh mục concept mới.
     */
    public function store(Request $request): JsonResponse
    {
        return $this->handleApi(function () use ($request) {
            $validated = $request->validate([
                'ten_danh_muc' => ['required', 'string', 'max:255'],
                'mo_ta' => ['nullable', 'string'],
            ]);

            $danhMuc = DanhMucConcept::create($validated);

            return response()->json($danhMuc, 201);

        }, 'tạo danh mục concept');
    }

    /**
     * Cập nhật danh mục concept.
     */
    public function update(Request $request, DanhMucConcept $danh_muc_concept): JsonResponse
    {
        return $this->handleApi(function () use ($request, $danh_muc_concept) {
            $validated = $request->validate([
                'ten_danh_muc' => ['required', 'string', 'max:255'],
                'mo_ta' => ['nullable', 'string'],
            ]);

            $danh_muc_concept->update($validated);

            return response()->json($danh_muc_concept->fresh());

        }, 'cập nhật danh mục concept');
    }

    /**
     * Xóa danh mục concept.
     */
    public function destroy(DanhMucConcept $danh_muc_concept): JsonResponse
    {
        return $this->handleApi(function () use ($danh_muc_concept) {
            if ($danh_muc_concept->concepts()->exists()) {
                return response()->json([
                    'message' => 'Không thể xóa danh mục đang có concept liên kết.',
                ], 422);
            }

            $danh_muc_concept->delete();

            return response()->json(['message' => 'Đã xóa danh mục concept.']);

        }, 'xóa danh mục concept');
    }
}
