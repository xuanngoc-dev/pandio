<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DichVuLoaiDichVu;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DichVuLoaiDichVuController extends Controller
{
    /**
     * Danh sách loại dịch vụ — phân trang + tìm kiếm.
     *
     * Query: page, per_page, keyword, trang_thai
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'keyword' => ['sometimes', 'nullable', 'string', 'max:255'],
            'trang_thai' => ['sometimes', 'nullable', Rule::in(['dang_hoat_dong', 'ngung_hoat_dong'])],
        ]);

        $perPage = $validated['per_page'] ?? 10;
        $keyword = trim((string) ($validated['keyword'] ?? ''));
        $trangThai = $validated['trang_thai'] ?? null;

        $query = DichVuLoaiDichVu::query()
            ->when($keyword !== '', function ($q) use ($keyword) {
                $q->where(function ($inner) use ($keyword) {
                    $inner->where('ten_dich_vu', 'like', "%{$keyword}%")
                        ->orWhere('mo_ta', 'like', "%{$keyword}%");
                });
            })
            ->when($trangThai, fn ($q) => $q->where('trang_thai', $trangThai))
            ->orderByDesc('id');

        return response()->json($query->paginate($perPage));
    }

    public function show(DichVuLoaiDichVu $dich_vu_loai_dich_vu): JsonResponse
    {
        return response()->json($dich_vu_loai_dich_vu);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ten_dich_vu' => ['required', 'string', 'max:255'],
            'mo_ta' => ['nullable', 'string'],
            'trang_thai' => ['sometimes', Rule::in(['dang_hoat_dong', 'ngung_hoat_dong'])],
        ]);

        $loaiDichVu = DichVuLoaiDichVu::create($validated);

        return response()->json($loaiDichVu, 201);
    }

    public function update(Request $request, DichVuLoaiDichVu $dich_vu_loai_dich_vu): JsonResponse
    {
        $validated = $request->validate([
            'ten_dich_vu' => ['required', 'string', 'max:255'],
            'mo_ta' => ['nullable', 'string'],
            'trang_thai' => ['sometimes', Rule::in(['dang_hoat_dong', 'ngung_hoat_dong'])],
        ]);

        $dich_vu_loai_dich_vu->update($validated);

        return response()->json($dich_vu_loai_dich_vu->fresh());
    }

    public function destroy(DichVuLoaiDichVu $dich_vu_loai_dich_vu): JsonResponse
    {
        if ($dich_vu_loai_dich_vu->danhSachDichVu()->exists()) {
            return response()->json([
                'message' => 'Không thể xóa loại dịch vụ đang được sử dụng bởi dịch vụ khác.',
            ], 422);
        }

        $dich_vu_loai_dich_vu->delete();

        return response()->json(['message' => 'Đã xóa loại dịch vụ.']);
    }
}
