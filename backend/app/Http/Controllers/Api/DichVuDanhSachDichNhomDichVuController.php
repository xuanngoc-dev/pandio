<?php

namespace App\Http\Controllers\Api;

use App\Models\DichVuDanhSachDichNhomDichVu;
use App\Models\DichVuDanhSachDichVuLe;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class DichVuDanhSachDichNhomDichVuController extends BaseApiController
{
    /**
     * Danh sách nhóm dịch vụ (combo) — phân trang + tìm kiếm.
     *
     * Query: page, per_page, keyword, trang_thai, loai_hop_dong_id
     */
    public function index(Request $request): JsonResponse
    {
        return $this->handleApi(function () use ($request) {
            $validated = $request->validate([
                'page' => ['sometimes', 'integer', 'min:1'],
                'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
                'keyword' => ['sometimes', 'nullable', 'string', 'max:255'],
                'trang_thai' => ['sometimes', 'nullable', Rule::in(['dang_su_dung', 'ngung_su_dung'])],
                'loai_hop_dong_id' => ['sometimes', 'nullable', 'integer', 'exists:loai_hop_dong,id'],
            ]);

            $perPage = $validated['per_page'] ?? 10;
            $keyword = trim((string) ($validated['keyword'] ?? ''));
            $trangThai = $validated['trang_thai'] ?? null;
            $loaiHopDongId = $validated['loai_hop_dong_id'] ?? null;

            $query = DichVuDanhSachDichNhomDichVu::query()
                ->with('loaiHopDong:id,ten_hop_dong,ma_hop_dong')
                ->when($keyword !== '', function ($q) use ($keyword) {
                    $q->where(function ($inner) use ($keyword) {
                        $inner->where('ma_nhom', 'like', "%{$keyword}%")
                            ->orWhere('ten_nhom', 'like', "%{$keyword}%");
                    });
                })
                ->when($trangThai, fn ($q) => $q->where('trang_thai', $trangThai))
                ->when($loaiHopDongId, fn ($q) => $q->where('loai_hop_dong_id', $loaiHopDongId))
                ->orderByDesc('id');

            $paginator = $query->paginate($perPage);
            $dichVuLeMap = $this->buildDichVuLeMap(
                $paginator->getCollection()->pluck('dich_vu_le_ids')->flatten()->unique()->filter()->all()
            );

            $paginator->getCollection()->transform(function (DichVuDanhSachDichNhomDichVu $item) use ($dichVuLeMap) {
                $item->dich_vu_le_labels = collect($item->dich_vu_le_ids ?? [])
                    ->map(fn ($id) => $dichVuLeMap[$id] ?? null)
                    ->filter()
                    ->values()
                    ->all();

                return $item;
            });

            return response()->json($paginator);

        }, 'lấy danh sách nhóm dịch vụ');
    }

    // {nhom_dich_vu} = route param rút ngắn cho DichVuDanhSachDichNhomDichVu (xem api.php).
    public function show(DichVuDanhSachDichNhomDichVu $nhom_dich_vu): JsonResponse
    {
        return $this->handleApi(function () use ($nhom_dich_vu) {
            $nhom_dich_vu->load('loaiHopDong:id,ten_hop_dong,ma_hop_dong');
            $this->appendDichVuLeLabels($nhom_dich_vu);

            return response()->json($nhom_dich_vu);

        }, 'lấy chi tiết nhóm dịch vụ');
    }

    public function store(Request $request): JsonResponse
    {
        return $this->handleApi(function () use ($request) {
            $validated = $this->validatePayload($request);

            $nhomDichVu = DichVuDanhSachDichNhomDichVu::create($validated);
            $nhomDichVu->load('loaiHopDong:id,ten_hop_dong,ma_hop_dong');
            $this->appendDichVuLeLabels($nhomDichVu);

            return response()->json($nhomDichVu, 201);

        }, 'tạo nhóm dịch vụ');
    }

    public function update(Request $request, DichVuDanhSachDichNhomDichVu $nhom_dich_vu): JsonResponse
    {
        return $this->handleApi(function () use ($request, $nhom_dich_vu) {
            $validated = $this->validatePayload($request, $nhom_dich_vu->id);

            $nhom_dich_vu->update($validated);

            $nhomDichVu = $nhom_dich_vu->fresh()->load('loaiHopDong:id,ten_hop_dong,ma_hop_dong');
            $this->appendDichVuLeLabels($nhomDichVu);

            return response()->json($nhomDichVu);

        }, 'cập nhật nhóm dịch vụ');
    }

    public function destroy(DichVuDanhSachDichNhomDichVu $nhom_dich_vu): JsonResponse
    {
        return $this->handleApi(function () use ($nhom_dich_vu) {
            $nhom_dich_vu->delete();

            return response()->json(['message' => 'Đã xóa nhóm dịch vụ.']);

        }, 'xóa nhóm dịch vụ');
    }

    private function validatePayload(Request $request, ?int $ignoreId = null): array
    {
        $validated = $request->validate([
            'ma_nhom' => [
                'required',
                'string',
                'max:50',
                Rule::unique('dich_vu_danh_sach_dich_nhom_dich_vu', 'ma_nhom')->ignore($ignoreId),
            ],
            'ten_nhom' => ['required', 'string', 'max:255'],
            'gia_goc' => ['required', 'numeric', 'min:0'],
            'gia_khuyen_mai' => ['nullable', 'numeric', 'min:0'],
            'loai_hop_dong_id' => ['required', 'integer', 'exists:loai_hop_dong,id'],
            'so_diem_chup' => ['required', 'integer', 'min:0'],
            'so_anh_chinh_sua' => ['required', 'integer', 'min:0'],
            'dich_vu_le_ids' => ['nullable', 'array'],
            'dich_vu_le_ids.*' => ['integer', 'exists:dich_vu_danh_sach_dich_vu_le,id'],
            'trang_thai' => ['sometimes', Rule::in(['dang_su_dung', 'ngung_su_dung'])],
            'ghi_chu' => ['nullable', 'string'],
        ]);

        if (! empty($validated['dich_vu_le_ids'])) {
            $loaiHopDongId = (int) $validated['loai_hop_dong_id'];
            $invalidIds = DichVuDanhSachDichVuLe::query()
                ->whereIn('id', $validated['dich_vu_le_ids'])
                ->get(['id', 'loai_hop_dong_ids'])
                ->filter(function (DichVuDanhSachDichVuLe $item) use ($loaiHopDongId) {
                    $ids = $item->loai_hop_dong_ids ?? [];

                    return ! in_array($loaiHopDongId, $ids, true);
                })
                ->pluck('id')
                ->all();

            if ($invalidIds !== []) {
                throw ValidationException::withMessages([
                    'dich_vu_le_ids' => ['Các dịch vụ lẻ được chọn phải áp dụng cho loại hợp đồng đã chọn.'],
                ]);
            }
        }

        return $validated;
    }

    /**
     * @param  array<int>  $ids
     * @return array<int, string>
     */
    private function buildDichVuLeMap(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        return DichVuDanhSachDichVuLe::query()
            ->whereIn('id', $ids)
            ->pluck('ten_dich_vu', 'id')
            ->all();
    }

    private function appendDichVuLeLabels(DichVuDanhSachDichNhomDichVu $item): void
    {
        $dichVuLeMap = $this->buildDichVuLeMap($item->dich_vu_le_ids ?? []);
        $item->dich_vu_le_labels = collect($item->dich_vu_le_ids ?? [])
            ->map(fn ($id) => $dichVuLeMap[$id] ?? null)
            ->filter()
            ->values()
            ->all();
    }
}
