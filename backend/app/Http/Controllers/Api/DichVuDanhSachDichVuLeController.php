<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DichVuDanhSachDichVuLe;
use App\Models\LoaiHopDong;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DichVuDanhSachDichVuLeController extends Controller
{
    /**
     * Danh sách dịch vụ lẻ — phân trang + tìm kiếm.
     *
     * Query: page, per_page, keyword, trang_thai, loai_dich_vu_id
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'keyword' => ['sometimes', 'nullable', 'string', 'max:255'],
            'trang_thai' => ['sometimes', 'nullable', Rule::in(['dang_su_dung', 'ngung_su_dung'])],
            'loai_dich_vu_id' => ['sometimes', 'nullable', 'integer', 'exists:dich_vu_loai_dich_vu,id'],
        ]);

        $perPage = $validated['per_page'] ?? 10;
        $keyword = trim((string) ($validated['keyword'] ?? ''));
        $trangThai = $validated['trang_thai'] ?? null;
        $loaiDichVuId = $validated['loai_dich_vu_id'] ?? null;

        $query = DichVuDanhSachDichVuLe::query()
            ->with('loaiDichVu:id,ten_dich_vu')
            ->when($keyword !== '', function ($q) use ($keyword) {
                $q->where(function ($inner) use ($keyword) {
                    $inner->where('ma_dich_vu', 'like', "%{$keyword}%")
                        ->orWhere('ten_dich_vu', 'like', "%{$keyword}%")
                        ->orWhere('mo_ta', 'like', "%{$keyword}%");
                });
            })
            ->when($trangThai, fn ($q) => $q->where('trang_thai', $trangThai))
            ->when($loaiDichVuId, fn ($q) => $q->where('loai_dich_vu_id', $loaiDichVuId))
            ->orderByDesc('id');

        $paginator = $query->paginate($perPage);
        $loaiHopDongMap = LoaiHopDong::query()
            ->pluck('ten_hop_dong', 'id')
            ->all();

        $paginator->getCollection()->transform(function (DichVuDanhSachDichVuLe $item) use ($loaiHopDongMap) {
            $ids = $item->loai_dich_vu_ids ?? [];
            $item->loai_hop_dong_labels = collect($ids)
                ->map(fn ($id) => $loaiHopDongMap[$id] ?? null)
                ->filter()
                ->values()
                ->all();

            return $item;
        });

        return response()->json($paginator);
    }

    public function show(DichVuDanhSachDichVuLe $dich_vu_danh_sach_dich_vu_le): JsonResponse
    {
        $dich_vu_danh_sach_dich_vu_le->load('loaiDichVu:id,ten_dich_vu');

        return response()->json($dich_vu_danh_sach_dich_vu_le);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validatePayload($request);

        $dichVu = DichVuDanhSachDichVuLe::create($validated);
        $dichVu->load('loaiDichVu:id,ten_dich_vu');

        return response()->json($dichVu, 201);
    }

    public function update(Request $request, DichVuDanhSachDichVuLe $dich_vu_danh_sach_dich_vu_le): JsonResponse
    {
        $validated = $this->validatePayload($request, $dich_vu_danh_sach_dich_vu_le->id);

        $dich_vu_danh_sach_dich_vu_le->update($validated);

        return response()->json($dich_vu_danh_sach_dich_vu_le->fresh()->load('loaiDichVu:id,ten_dich_vu'));
    }

    public function destroy(DichVuDanhSachDichVuLe $dich_vu_danh_sach_dich_vu_le): JsonResponse
    {
        $dich_vu_danh_sach_dich_vu_le->delete();

        return response()->json(['message' => 'Đã xóa dịch vụ.']);
    }

    private function validatePayload(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'ma_dich_vu' => [
                'required',
                'string',
                'max:50',
                Rule::unique('dich_vu_danh_sach_dich_vu_le', 'ma_dich_vu')->ignore($ignoreId),
            ],
            'ten_dich_vu' => ['required', 'string', 'max:255'],
            'loai_dich_vu_id' => ['required', 'integer', 'exists:dich_vu_loai_dich_vu,id'],
            'loai_dich_vu_ids' => ['nullable', 'array'],
            'loai_dich_vu_ids.*' => ['integer', 'exists:loai_hop_dong,id'],
            'gia_goc' => ['required', 'numeric', 'min:0'],
            'gia_khuyen_mai' => ['nullable', 'numeric', 'min:0'],
            'mo_ta' => ['nullable', 'string'],
            'trang_thai' => ['sometimes', Rule::in(['dang_su_dung', 'ngung_su_dung'])],
            'ghi_chu' => ['nullable', 'string'],
        ]);
    }
}
