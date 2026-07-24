<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CauHinhChiNhanh;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CauHinhChiNhanhController extends Controller
{
    /**
     * Danh sách chi nhánh — phân trang + tìm kiếm.
     *
     * Query: page, per_page, keyword
     */
    public function index(Request $request): JsonResponse
    {
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
    }

    /**
     * Chi tiết một chi nhánh.
     */
    public function show(CauHinhChiNhanh $cau_hinh_chi_nhanh): JsonResponse
    {
        return response()->json($cau_hinh_chi_nhanh);
    }

    /**
     * Tạo chi nhánh mới.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $this->validatePayload($request);

        $chiNhanh = CauHinhChiNhanh::create($validated);

        return response()->json($chiNhanh, 201);
    }

    /**
     * Cập nhật chi nhánh.
     */
    public function update(Request $request, CauHinhChiNhanh $cau_hinh_chi_nhanh): JsonResponse
    {
        $validated = $this->validatePayload($request);

        $cau_hinh_chi_nhanh->update($validated);

        return response()->json($cau_hinh_chi_nhanh->fresh());
    }

    /**
     * Xóa chi nhánh.
     */
    public function destroy(CauHinhChiNhanh $cau_hinh_chi_nhanh): JsonResponse
    {
        $cau_hinh_chi_nhanh->delete();

        return response()->json(['message' => 'Đã xóa chi nhánh.']);
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
