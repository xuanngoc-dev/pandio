<?php

namespace App\Http\Controllers\Api;

use App\Models\VaiTro;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VaiTroController extends BaseApiController
{
    /**
     * Danh sách vai trò — phân trang + tìm kiếm.
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

            $query = VaiTro::query()
                ->when($keyword !== '', function ($q) use ($keyword) {
                    $q->where(function ($inner) use ($keyword) {
                        $inner->where('ten_vai_tro', 'like', "%{$keyword}%")
                            ->orWhere('ghi_chu', 'like', "%{$keyword}%");
                    });
                })
                ->orderByDesc('id');

            return response()->json($query->paginate($perPage));

        }, 'lấy danh sách vai trò');
    }

    /**
     * Chi tiết một vai trò.
     */
    public function show(VaiTro $vai_tro): JsonResponse
    {
        return $this->handleApi(function () use ($vai_tro) {
            return response()->json($vai_tro);

        }, 'lấy chi tiết vai trò');
    }

    /**
     * Tạo vai trò mới.
     */
    public function store(Request $request): JsonResponse
    {
        return $this->handleApi(function () use ($request) {
            $validated = $this->validatePayload($request);

            $item = VaiTro::create($validated);

            return response()->json($item, 201);

        }, 'tạo vai trò');
    }

    /**
     * Cập nhật vai trò.
     */
    public function update(Request $request, VaiTro $vai_tro): JsonResponse
    {
        return $this->handleApi(function () use ($request, $vai_tro) {
            $validated = $this->validatePayload($request);

            $vai_tro->update($validated);

            return response()->json($vai_tro->fresh());

        }, 'cập nhật vai trò');
    }

    /**
     * Xóa vai trò.
     */
    public function destroy(VaiTro $vai_tro): JsonResponse
    {
        return $this->handleApi(function () use ($vai_tro) {
            $vai_tro->delete();

            return response()->json(['message' => 'Đã xóa vai trò.']);

        }, 'xóa vai trò');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatePayload(Request $request): array
    {
        return $request->validate([
            'ten_vai_tro' => ['required', 'string', 'max:255'],
            'ghi_chu' => ['nullable', 'string', 'max:2000'],
            'danh_sach_menu' => ['nullable', 'array'],
            'danh_sach_menu.*' => ['string', 'max:255'],
            'cau_hinh' => ['nullable', 'array'],
            'cau_hinh.*' => ['string', 'max:255'],
        ]);
    }
}
