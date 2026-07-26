<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DichVuDanhSachDichNhomDichVu;
use App\Models\DichVuDanhSachDichVuLe;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class DichVuDanhSachDichNhomDichVuController extends Controller
{
    /**
     * Danh sách nhóm dịch vụ (combo) — phân trang + tìm kiếm.
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

        $query = DichVuDanhSachDichNhomDichVu::query()
            ->with('loaiDichVu:id,ten_dich_vu')
            ->when($keyword !== '', function ($q) use ($keyword) {
                $q->where(function ($inner) use ($keyword) {
                    $inner->where('ma_nhom', 'like', "%{$keyword}%")
                        ->orWhere('ten_nhom', 'like', "%{$keyword}%");
                });
            })
            ->when($trangThai, fn ($q) => $q->where('trang_thai', $trangThai))
            ->when($loaiDichVuId, fn ($q) => $q->where('loai_dich_vu_id', $loaiDichVuId))
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
    }

    public function show(DichVuDanhSachDichNhomDichVu $dich_vu_danh_sach_dich_nhom_dich_vu): JsonResponse
    {
        $dich_vu_danh_sach_dich_nhom_dich_vu->load('loaiDichVu:id,ten_dich_vu');
        $this->appendDichVuLeLabels($dich_vu_danh_sach_dich_nhom_dich_vu);

        return response()->json($dich_vu_danh_sach_dich_nhom_dich_vu);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validatePayload($request);

        $nhomDichVu = DichVuDanhSachDichNhomDichVu::create($validated);
        $nhomDichVu->load('loaiDichVu:id,ten_dich_vu');
        $this->appendDichVuLeLabels($nhomDichVu);

        return response()->json($nhomDichVu, 201);
    }

    public function update(Request $request, DichVuDanhSachDichNhomDichVu $dich_vu_danh_sach_dich_nhom_dich_vu): JsonResponse
    {
        $validated = $this->validatePayload($request, $dich_vu_danh_sach_dich_nhom_dich_vu->id);

        $dich_vu_danh_sach_dich_nhom_dich_vu->update($validated);

        $nhomDichVu = $dich_vu_danh_sach_dich_nhom_dich_vu->fresh()->load('loaiDichVu:id,ten_dich_vu');
        $this->appendDichVuLeLabels($nhomDichVu);

        return response()->json($nhomDichVu);
    }

    public function destroy(DichVuDanhSachDichNhomDichVu $dich_vu_danh_sach_dich_nhom_dich_vu): JsonResponse
    {
        $dich_vu_danh_sach_dich_nhom_dich_vu->delete();

        return response()->json(['message' => 'Đã xóa nhóm dịch vụ.']);
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
            'loai_dich_vu_id' => ['required', 'integer', 'exists:dich_vu_loai_dich_vu,id'],
            'so_diem_chup' => ['required', 'integer', 'min:0'],
            'so_anh_chinh_sua' => ['required', 'integer', 'min:0'],
            'dich_vu_le_ids' => ['nullable', 'array'],
            'dich_vu_le_ids.*' => ['integer', 'exists:dich_vu_danh_sach_dich_vu_le,id'],
            'trang_thai' => ['sometimes', Rule::in(['dang_su_dung', 'ngung_su_dung'])],
            'ghi_chu' => ['nullable', 'string'],
        ]);

        if (! empty($validated['dich_vu_le_ids'])) {
            $invalidIds = DichVuDanhSachDichVuLe::query()
                ->whereIn('id', $validated['dich_vu_le_ids'])
                ->where('loai_dich_vu_id', '!=', $validated['loai_dich_vu_id'])
                ->pluck('id')
                ->all();

            if ($invalidIds !== []) {
                throw ValidationException::withMessages([
                    'dich_vu_le_ids' => ['Các dịch vụ lẻ được chọn phải thuộc loại dịch vụ đã chọn.'],
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
