<?php

namespace App\Http\Controllers\Api;

use App\Models\PhieuThuChi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PhieuThuChiController extends BaseApiController
{
    /**
     * Danh sách phiếu thu chi — phân trang + tìm kiếm / lọc.
     *
     * Query: page, per_page, keyword, loai, trang_thai, hang_muc_id
     */
    public function index(Request $request): JsonResponse
    {
        return $this->handleApi(function () use ($request) {
            $validated = $request->validate([
                'page' => ['sometimes', 'integer', 'min:1'],
                'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
                'keyword' => ['sometimes', 'nullable', 'string', 'max:255'],
                'loai' => ['sometimes', 'nullable', Rule::in(['thu', 'chi'])],
                'trang_thai' => ['sometimes', 'nullable', Rule::in(['cho_duyet', 'da_duyet', 'tu_choi'])],
                'hang_muc_id' => ['sometimes', 'nullable', 'integer', 'exists:hang_muc_loai_thu_chi,id'],
            ]);

            $perPage = $validated['per_page'] ?? 10;
            $keyword = trim((string) ($validated['keyword'] ?? ''));

            $query = PhieuThuChi::query()
                ->with([
                    'nguoiTao:id,name,phone',
                    'nguoiDuyet:id,name,phone',
                    'hangMuc:id,ten_hang_muc,trang_thai',
                ])
                ->when($keyword !== '', function ($q) use ($keyword) {
                    $q->where(function ($inner) use ($keyword) {
                        $inner->where('ly_do', 'like', "%{$keyword}%")
                            ->orWhere('ghi_chu', 'like', "%{$keyword}%");
                    });
                })
                ->when(! empty($validated['loai']), fn ($q) => $q->where('loai', $validated['loai']))
                ->when(! empty($validated['trang_thai']), fn ($q) => $q->where('trang_thai', $validated['trang_thai']))
                ->when(! empty($validated['hang_muc_id']), fn ($q) => $q->where('hang_muc_id', $validated['hang_muc_id']))
                ->orderByDesc('id');

            return response()->json($query->paginate($perPage));

        }, 'lấy danh sách phiếu thu chi');
    }

    public function show(PhieuThuChi $phieu_thu_chi): JsonResponse
    {
        return $this->handleApi(function () use ($phieu_thu_chi) {
            $phieu_thu_chi->load([
                'nguoiTao:id,name,phone',
                'nguoiDuyet:id,name,phone',
                'hangMuc:id,ten_hang_muc,trang_thai',
            ]);

            return response()->json($phieu_thu_chi);

        }, 'lấy chi tiết phiếu thu chi');
    }

    public function store(Request $request): JsonResponse
    {
        return $this->handleApi(function () use ($request) {
            $validated = $this->validatePayload($request);
            $validated['nguoi_tao_id'] = $request->user()->id;
            $validated['trang_thai'] = $validated['trang_thai'] ?? 'cho_duyet';
            $validated['ngay_cap_nhat_trang_thai'] = now();

            if (($validated['trang_thai'] ?? '') === 'da_duyet') {
                $validated['nguoi_duyet_id'] = $request->user()->id;
            }

            $item = PhieuThuChi::create($validated);
            $item->load([
                'nguoiTao:id,name,phone',
                'nguoiDuyet:id,name,phone',
                'hangMuc:id,ten_hang_muc,trang_thai',
            ]);

            return response()->json($item, 201);

        }, 'tạo phiếu thu chi');
    }

    public function update(Request $request, PhieuThuChi $phieu_thu_chi): JsonResponse
    {
        return $this->handleApi(function () use ($request, $phieu_thu_chi) {
            $validated = $this->validatePayload($request);
            unset($validated['nguoi_tao_id']);

            $trangThaiMoi = $validated['trang_thai'] ?? $phieu_thu_chi->trang_thai;
            if ($trangThaiMoi !== $phieu_thu_chi->trang_thai) {
                $validated['ngay_cap_nhat_trang_thai'] = now();
                if ($trangThaiMoi === 'da_duyet' || $trangThaiMoi === 'tu_choi') {
                    $validated['nguoi_duyet_id'] = $request->user()->id;
                }
            }

            $phieu_thu_chi->update($validated);

            return response()->json($phieu_thu_chi->fresh()->load([
                'nguoiTao:id,name,phone',
                'nguoiDuyet:id,name,phone',
                'hangMuc:id,ten_hang_muc,trang_thai',
            ]));

        }, 'cập nhật phiếu thu chi');
    }

    public function destroy(PhieuThuChi $phieu_thu_chi): JsonResponse
    {
        return $this->handleApi(function () use ($phieu_thu_chi) {
            $phieu_thu_chi->delete();

            return response()->json(['message' => 'Đã xóa phiếu thu chi.']);

        }, 'xóa phiếu thu chi');
    }

    /**
     * Xóa nhiều phiếu thu chi.
     */
    public function bulkDestroy(Request $request): JsonResponse
    {
        return $this->handleApi(function () use ($request) {
            $validated = $request->validate([
                'ids' => ['required', 'array', 'min:1'],
                'ids.*' => ['integer', 'exists:phieu_thu_chi,id'],
            ]);

            $deleted = PhieuThuChi::query()->whereIn('id', $validated['ids'])->delete();

            return response()->json([
                'message' => "Đã xóa {$deleted} phiếu thu chi.",
                'deleted' => $deleted,
            ]);

        }, 'xóa nhiều phiếu thu chi');
    }

    /**
     * Cập nhật trạng thái nhiều phiếu thu chi.
     */
    public function bulkUpdateStatus(Request $request): JsonResponse
    {
        return $this->handleApi(function () use ($request) {
            $validated = $request->validate([
                'ids' => ['required', 'array', 'min:1'],
                'ids.*' => ['integer', 'exists:phieu_thu_chi,id'],
                'trang_thai' => ['required', Rule::in(['cho_duyet', 'da_duyet', 'tu_choi'])],
                'ghi_chu' => ['required', 'string', 'max:2000'],
            ]);

            $payload = [
                'trang_thai' => $validated['trang_thai'],
                'ghi_chu' => $validated['ghi_chu'],
                'ngay_cap_nhat_trang_thai' => now(),
            ];

            if (in_array($validated['trang_thai'], ['da_duyet', 'tu_choi'], true)) {
                $payload['nguoi_duyet_id'] = $request->user()->id;
            }

            $updated = PhieuThuChi::query()
                ->whereIn('id', $validated['ids'])
                ->update($payload);

            return response()->json([
                'message' => "Đã cập nhật trạng thái {$updated} phiếu thu chi.",
                'updated' => $updated,
            ]);

        }, 'cập nhật trạng thái nhiều phiếu thu chi');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatePayload(Request $request): array
    {
        return $request->validate([
            'nguoi_tao_id' => ['nullable', 'integer', 'exists:users,id'],
            'nguoi_duyet_id' => ['nullable', 'integer', 'exists:users,id'],
            'loai' => ['required', Rule::in(['thu', 'chi'])],
            'hang_muc_id' => ['nullable', 'integer', 'exists:hang_muc_loai_thu_chi,id'],
            'so_tien' => ['required', 'numeric', 'min:0'],
            'ly_do' => ['nullable', 'string'],
            'trang_thai' => ['sometimes', Rule::in(['cho_duyet', 'da_duyet', 'tu_choi'])],
            'ngay_cap_nhat_trang_thai' => ['nullable', 'date'],
            'ghi_chu' => ['nullable', 'string'],
        ]);
    }
}
