<?php

namespace App\Http\Controllers\Api;

use App\Models\DanhMucLoaiThongBao;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class DanhMucLoaiThongBaoController extends BaseApiController
{
    /**
     * Danh sách loại thông báo — phân trang + tìm kiếm.
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
                'trang_thai' => ['sometimes', 'nullable', 'in:dang_su_dung,ngung_su_dung'],
            ]);

            $perPage = $validated['per_page'] ?? 10;
            $keyword = trim((string) ($validated['keyword'] ?? ''));
            $trangThai = $validated['trang_thai'] ?? null;

            $query = DanhMucLoaiThongBao::query()
                ->when($keyword !== '', function ($q) use ($keyword) {
                    $q->where(function ($inner) use ($keyword) {
                        $inner->where('ma_loai_thong_bao', 'like', "%{$keyword}%")
                            ->orWhere('ten_loai_thong_bao', 'like', "%{$keyword}%")
                            ->orWhere('icon', 'like', "%{$keyword}%");
                    });
                })
                ->when($trangThai, fn ($q) => $q->where('trang_thai', $trangThai))
                ->orderByDesc('id');

            return response()->json($query->paginate($perPage));

        }, 'lấy danh sách loại thông báo');
    }

    /**
     * Chi tiết một loại thông báo.
     */
    public function show(DanhMucLoaiThongBao $danh_muc_loai_thong_bao): JsonResponse
    {
        return $this->handleApi(function () use ($danh_muc_loai_thong_bao) {
            return response()->json($danh_muc_loai_thong_bao);

        }, 'lấy chi tiết loại thông báo');
    }

    /**
     * Tạo loại thông báo mới.
     */
    public function store(Request $request): JsonResponse
    {
        return $this->handleApi(function () use ($request) {
            $validated = $request->validate([
                'ma_loai_thong_bao' => [
                    'required',
                    'string',
                    'max:100',
                    'unique:danh_muc_loai_thong_bao,ma_loai_thong_bao',
                ],
                'ten_loai_thong_bao' => ['required', 'string', 'max:255'],
                'icon' => ['nullable', 'string', 'max:100'],
                'trang_thai' => ['required', 'in:dang_su_dung,ngung_su_dung'],
            ]);

            $item = DanhMucLoaiThongBao::create($validated);

            return response()->json($item, 201);

        }, 'tạo loại thông báo');
    }

    /**
     * Cập nhật loại thông báo.
     */
    public function update(Request $request, DanhMucLoaiThongBao $danh_muc_loai_thong_bao): JsonResponse
    {
        return $this->handleApi(function () use ($request, $danh_muc_loai_thong_bao) {
            $validated = $request->validate([
                'ma_loai_thong_bao' => [
                    'required',
                    'string',
                    'max:100',
                    Rule::unique('danh_muc_loai_thong_bao', 'ma_loai_thong_bao')
                        ->ignore($danh_muc_loai_thong_bao->id),
                ],
                'ten_loai_thong_bao' => ['required', 'string', 'max:255'],
                'icon' => ['nullable', 'string', 'max:100'],
                'trang_thai' => ['required', 'in:dang_su_dung,ngung_su_dung'],
            ]);

            $danh_muc_loai_thong_bao->update($validated);

            return response()->json($danh_muc_loai_thong_bao->fresh());

        }, 'cập nhật loại thông báo');
    }

    /**
     * Xóa loại thông báo.
     */
    public function destroy(DanhMucLoaiThongBao $danh_muc_loai_thong_bao): JsonResponse
    {
        return $this->handleApi(function () use ($danh_muc_loai_thong_bao) {
            $inUse = DB::table('he_thong_thong_bao')
                ->where('loai_thong_bao_id', $danh_muc_loai_thong_bao->id)
                ->exists();

            if ($inUse) {
                return response()->json([
                    'message' => 'Không thể xóa loại thông báo đang có thông báo liên kết.',
                ], 422);
            }

            $danh_muc_loai_thong_bao->delete();

            return response()->json(['message' => 'Đã xóa loại thông báo.']);

        }, 'xóa loại thông báo');
    }
}
