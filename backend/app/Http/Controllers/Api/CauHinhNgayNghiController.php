<?php

namespace App\Http\Controllers\Api;

use App\Models\CauHinhNgayNghi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CauHinhNgayNghiController extends BaseApiController
{
    /**
     * Danh sách ngày nghỉ — phân trang + tìm kiếm.
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

            $query = CauHinhNgayNghi::query()
                ->when($keyword !== '', function ($q) use ($keyword) {
                    $q->where('ten_ngay_nghi', 'like', "%{$keyword}%");
                })
                ->when($trangThai, fn ($q) => $q->where('trang_thai', $trangThai))
                ->orderByDesc('ngay_bat_dau')
                ->orderByDesc('id');

            return response()->json($query->paginate($perPage));

        }, 'lấy danh sách ngày nghỉ');
    }

    /**
     * Chi tiết một ngày nghỉ.
     */
    public function show(CauHinhNgayNghi $cau_hinh_ngay_nghi): JsonResponse
    {
        return $this->handleApi(function () use ($cau_hinh_ngay_nghi) {
            return response()->json($cau_hinh_ngay_nghi);

        }, 'lấy chi tiết ngày nghỉ');
    }

    /**
     * Tạo ngày nghỉ mới.
     */
    public function store(Request $request): JsonResponse
    {
        return $this->handleApi(function () use ($request) {
            $validated = $this->validatePayload($request);

            $item = CauHinhNgayNghi::create($validated);

            return response()->json($item, 201);

        }, 'tạo ngày nghỉ');
    }

    /**
     * Cập nhật ngày nghỉ.
     */
    public function update(Request $request, CauHinhNgayNghi $cau_hinh_ngay_nghi): JsonResponse
    {
        return $this->handleApi(function () use ($request, $cau_hinh_ngay_nghi) {
            $validated = $this->validatePayload($request);

            $cau_hinh_ngay_nghi->update($validated);

            return response()->json($cau_hinh_ngay_nghi->fresh());

        }, 'cập nhật ngày nghỉ');
    }

    /**
     * Xóa ngày nghỉ.
     */
    public function destroy(CauHinhNgayNghi $cau_hinh_ngay_nghi): JsonResponse
    {
        return $this->handleApi(function () use ($cau_hinh_ngay_nghi) {
            $cau_hinh_ngay_nghi->delete();

            return response()->json(['message' => 'Đã xóa ngày nghỉ.']);

        }, 'xóa ngày nghỉ');
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
