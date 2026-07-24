<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CauHinhNgayNghi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CauHinhNgayNghiController extends Controller
{
    /**
     * Danh sách ngày nghỉ — phân trang + tìm kiếm.
     *
     * Query: page, per_page, keyword, trang_thai
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'keyword' => ['sometimes', 'nullable', 'string', 'max:255'],
            'trang_thai' => ['sometimes', 'nullable', 'string', Rule::in(['active', 'inactive'])],
        ]);

        $perPage = $validated['per_page'] ?? 10;
        $keyword = trim((string) ($validated['keyword'] ?? ''));
        $trangThai = $validated['trang_thai'] ?? null;

        $query = CauHinhNgayNghi::query()
            ->when($keyword !== '', function ($q) use ($keyword) {
                $q->where('ten_ngay_nghi', 'like', "%{$keyword}%");
            })
            ->when($trangThai, fn ($q) => $q->where('trang_thai', $trangThai))
            ->orderByDesc('ngay_bat_dau')
            ->orderByDesc('id');

        return response()->json($query->paginate($perPage));
    }

    /**
     * Chi tiết một ngày nghỉ.
     */
    public function show(CauHinhNgayNghi $cau_hinh_ngay_nghi): JsonResponse
    {
        return response()->json($cau_hinh_ngay_nghi);
    }

    /**
     * Tạo ngày nghỉ mới.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $this->validatePayload($request);

        $item = CauHinhNgayNghi::create($validated);

        return response()->json($item, 201);
    }

    /**
     * Cập nhật ngày nghỉ.
     */
    public function update(Request $request, CauHinhNgayNghi $cau_hinh_ngay_nghi): JsonResponse
    {
        $validated = $this->validatePayload($request);

        $cau_hinh_ngay_nghi->update($validated);

        return response()->json($cau_hinh_ngay_nghi->fresh());
    }

    /**
     * Xóa ngày nghỉ.
     */
    public function destroy(CauHinhNgayNghi $cau_hinh_ngay_nghi): JsonResponse
    {
        $cau_hinh_ngay_nghi->delete();

        return response()->json(['message' => 'Đã xóa ngày nghỉ.']);
    }

    /**
     * @return array<string, mixed>
     */
    private function validatePayload(Request $request): array
    {
        return $request->validate([
            'ten_ngay_nghi' => ['required', 'string', 'max:255'],
            'ngay_bat_dau' => ['required', 'date'],
            'ngay_ket_thuc' => ['required', 'date', 'after_or_equal:ngay_bat_dau'],
            'trang_thai' => ['required', 'string', Rule::in(['active', 'inactive'])],
        ]);
    }
}
