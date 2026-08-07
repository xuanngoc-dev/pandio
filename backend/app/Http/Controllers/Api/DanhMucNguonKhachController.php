<?php

namespace App\Http\Controllers\Api;

use App\Models\DanhMucNguonKhach;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class DanhMucNguonKhachController extends BaseApiController
{
    /**
     * Danh sách nguồn khách — phân trang + tìm kiếm.
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

            $query = DanhMucNguonKhach::query()
                ->when($keyword !== '', function ($q) use ($keyword) {
                    $q->where(function ($inner) use ($keyword) {
                        $inner->where('ten_nguon_khach', 'like', "%{$keyword}%")
                            ->orWhere('ghi_chu', 'like', "%{$keyword}%");
                    });
                })
                ->when($trangThai, fn ($q) => $q->where('trang_thai', $trangThai))
                ->orderByDesc('id');

            return response()->json($query->paginate($perPage));

        }, 'lấy danh sách nguồn khách');
    }

    /**
     * Chi tiết một nguồn khách.
     */
    public function show(DanhMucNguonKhach $danh_muc_nguon_khach): JsonResponse
    {
        return $this->handleApi(function () use ($danh_muc_nguon_khach) {
            return response()->json($danh_muc_nguon_khach);

        }, 'lấy chi tiết nguồn khách');
    }

    /**
     * Tạo nguồn khách mới.
     */
    public function store(Request $request): JsonResponse
    {
        return $this->handleApi(function () use ($request) {
            $validated = $request->validate([
                'ten_nguon_khach' => ['required', 'string', 'max:255'],
                'ghi_chu' => ['nullable', 'string'],
                'trang_thai' => ['required', 'string', Rule::in(['active', 'inactive'])],
            ]);

            $item = DanhMucNguonKhach::create($validated);

            return response()->json($item, 201);

        }, 'tạo nguồn khách');
    }

    /**
     * Cập nhật nguồn khách.
     */
    public function update(Request $request, DanhMucNguonKhach $danh_muc_nguon_khach): JsonResponse
    {
        return $this->handleApi(function () use ($request, $danh_muc_nguon_khach) {
            $validated = $request->validate([
                'ten_nguon_khach' => ['required', 'string', 'max:255'],
                'ghi_chu' => ['nullable', 'string'],
                'trang_thai' => ['required', 'string', Rule::in(['active', 'inactive'])],
            ]);

            $danh_muc_nguon_khach->update($validated);

            return response()->json($danh_muc_nguon_khach->fresh());

        }, 'cập nhật nguồn khách');
    }

    /**
     * Xóa nguồn khách.
     */
    public function destroy(DanhMucNguonKhach $danh_muc_nguon_khach): JsonResponse
    {
        return $this->handleApi(function () use ($danh_muc_nguon_khach) {
            $ten = $danh_muc_nguon_khach->ten_nguon_khach;

            $usedInNote = DB::table('khach_hang_note_khach_moi')
                ->where('nguon_khach', $ten)
                ->exists();

            $usedInHopDong = DB::table('hop_dong_su_dung_dich_vu')
                ->where('kenh_tiep_can', $ten)
                ->exists();

            if ($usedInNote || $usedInHopDong) {
                return response()->json([
                    'message' => 'Không thể xóa nguồn khách đang được dùng trong note khách mới hoặc hợp đồng.',
                ], 422);
            }

            $danh_muc_nguon_khach->delete();

            return response()->json(['message' => 'Đã xóa nguồn khách.']);

        }, 'xóa nguồn khách');
    }
}
