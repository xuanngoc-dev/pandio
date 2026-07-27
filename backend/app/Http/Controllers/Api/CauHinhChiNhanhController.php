<?php

namespace App\Http\Controllers\Api;

use App\Models\CauHinhChiNhanh;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CauHinhChiNhanhController extends BaseApiController
{
    /**
     * Danh sách chi nhánh — phân trang + tìm kiếm.
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

            $query = CauHinhChiNhanh::query()
                ->when($keyword !== '', function ($q) use ($keyword) {
                    $q->where(function ($inner) use ($keyword) {
                        $inner->where('ten_chi_nhanh', 'like', "%{$keyword}%")
                            ->orWhere('dia_chi', 'like', "%{$keyword}%")
                            ->orWhere('so_dien_thoai', 'like', "%{$keyword}%")
                            ->orWhere('email', 'like', "%{$keyword}%")
                            ->orWhere('truong_chi_nhanh', 'like', "%{$keyword}%");
                    });
                })
                ->orderByDesc('id');

            return response()->json($query->paginate($perPage));

        }, 'lấy danh sách chi nhánh');
    }

    /**
     * Chi tiết một chi nhánh.
     */
    public function show(CauHinhChiNhanh $cau_hinh_chi_nhanh): JsonResponse
    {
        return $this->handleApi(function () use ($cau_hinh_chi_nhanh) {
            return response()->json($cau_hinh_chi_nhanh);

        }, 'lấy chi tiết chi nhánh');
    }

    /**
     * Tạo chi nhánh mới.
     */
    public function store(Request $request): JsonResponse
    {
        return $this->handleApi(function () use ($request) {
            $validated = $this->validatePayload($request);

            $chiNhanh = CauHinhChiNhanh::create($validated);

            return response()->json($chiNhanh, 201);

        }, 'tạo chi nhánh');
    }

    /**
     * Cập nhật chi nhánh.
     */
    public function update(Request $request, CauHinhChiNhanh $cau_hinh_chi_nhanh): JsonResponse
    {
        return $this->handleApi(function () use ($request, $cau_hinh_chi_nhanh) {
            $validated = $this->validatePayload($request);

            $cau_hinh_chi_nhanh->update($validated);

            return response()->json($cau_hinh_chi_nhanh->fresh());

        }, 'cập nhật chi nhánh');
    }

    /**
     * Xóa chi nhánh.
     */
    public function destroy(CauHinhChiNhanh $cau_hinh_chi_nhanh): JsonResponse
    {
        return $this->handleApi(function () use ($cau_hinh_chi_nhanh) {
            $cau_hinh_chi_nhanh->delete();

            return response()->json(['message' => 'Đã xóa chi nhánh.']);

        }, 'xóa chi nhánh');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatePayload(Request $request): array
    {
        return $request->validate([
            'ten_chi_nhanh' => ['required', 'string', 'max:255'],
            'dia_chi' => ['required', 'string', 'max:255'],
            'so_dien_thoai' => ['required', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'truong_chi_nhanh' => ['required', 'string', 'max:255'],
        ]);
    }
}
