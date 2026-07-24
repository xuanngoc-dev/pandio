<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NhaCungCapTrangPhuc;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class NhaCungCapTrangPhucController extends Controller
{
    /**
     * Danh sách nhà cung cấp trang phục — phân trang + tìm kiếm.
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

        $query = NhaCungCapTrangPhuc::query()
            ->when($keyword !== '', function ($q) use ($keyword) {
                $q->where(function ($inner) use ($keyword) {
                    $inner->where('ma_nha_cung_cap', 'like', "%{$keyword}%")
                        ->orWhere('ten_nha_cung_cap', 'like', "%{$keyword}%")
                        ->orWhere('dia_chi', 'like', "%{$keyword}%")
                        ->orWhere('so_dien_thoai', 'like', "%{$keyword}%")
                        ->orWhere('email', 'like', "%{$keyword}%")
                        ->orWhere('ghi_chu', 'like', "%{$keyword}%");
                });
            })
            ->orderByDesc('id');

        return response()->json($query->paginate($perPage));
    }

    /**
     * Chi tiết một nhà cung cấp trang phục.
     */
    public function show(NhaCungCapTrangPhuc $nha_cung_cap_trang_phuc): JsonResponse
    {
        return response()->json($nha_cung_cap_trang_phuc);
    }

    /**
     * Tạo nhà cung cấp trang phục mới.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ma_nha_cung_cap' => ['required', 'string', 'max:50', 'unique:nha_cung_cap_trang_phuc,ma_nha_cung_cap'],
            'ten_nha_cung_cap' => ['required', 'string', 'max:255'],
            'dia_chi' => ['nullable', 'string', 'max:500'],
            'so_dien_thoai' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'ghi_chu' => ['nullable', 'string'],
        ]);

        $nhaCungCap = NhaCungCapTrangPhuc::create($validated);

        return response()->json($nhaCungCap, 201);
    }

    /**
     * Cập nhật nhà cung cấp trang phục.
     */
    public function update(Request $request, NhaCungCapTrangPhuc $nha_cung_cap_trang_phuc): JsonResponse
    {
        $validated = $request->validate([
            'ma_nha_cung_cap' => [
                'required',
                'string',
                'max:50',
                Rule::unique('nha_cung_cap_trang_phuc', 'ma_nha_cung_cap')->ignore($nha_cung_cap_trang_phuc->id),
            ],
            'ten_nha_cung_cap' => ['required', 'string', 'max:255'],
            'dia_chi' => ['nullable', 'string', 'max:500'],
            'so_dien_thoai' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'ghi_chu' => ['nullable', 'string'],
        ]);

        $nha_cung_cap_trang_phuc->update($validated);

        return response()->json($nha_cung_cap_trang_phuc->fresh());
    }

    /**
     * Xóa nhà cung cấp trang phục.
     */
    public function destroy(NhaCungCapTrangPhuc $nha_cung_cap_trang_phuc): JsonResponse
    {
        $nha_cung_cap_trang_phuc->delete();

        return response()->json(['message' => 'Đã xóa nhà cung cấp trang phục.']);
    }
}
