<?php

namespace App\Http\Controllers\Api;

use App\Models\DatMuaTrangPhuc;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class DatMuaTrangPhucController extends BaseApiController
{
    private const TRANG_THAI = ['cho_duyet', 'da_duyet', 'huy_duyet'];

    /**
     * Danh sách đặt mua trang phục — phân trang + tìm kiếm.
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
                'trang_thai' => ['sometimes', 'nullable', 'string', Rule::in(self::TRANG_THAI)],
            ]);

            $perPage = $validated['per_page'] ?? 10;
            $keyword = trim((string) ($validated['keyword'] ?? ''));
            $trangThai = $validated['trang_thai'] ?? null;

            $query = DatMuaTrangPhuc::query()
                ->with('nhaCungCap:id,ma_nha_cung_cap,ten_nha_cung_cap')
                ->when($keyword !== '', function ($q) use ($keyword) {
                    $q->where(function ($inner) use ($keyword) {
                        $inner->where('loai_don_hang', 'like', "%{$keyword}%")
                            ->orWhere('nguon_hang_hoa', 'like', "%{$keyword}%")
                            ->orWhereHas('nhaCungCap', function ($ncc) use ($keyword) {
                                $ncc->where('ma_nha_cung_cap', 'like', "%{$keyword}%")
                                    ->orWhere('ten_nha_cung_cap', 'like', "%{$keyword}%");
                            });
                    });
                })
                ->when($trangThai, fn ($q) => $q->where('trang_thai', $trangThai))
                ->orderByDesc('ngay_dat')
                ->orderByDesc('id');

            return response()->json($query->paginate($perPage));

        }, 'lấy danh sách đặt mua trang phục');
    }

    /**
     * Chi tiết một đơn đặt mua trang phục.
     */
    public function show(DatMuaTrangPhuc $dat_mua_trang_phuc): JsonResponse
    {
        return $this->handleApi(function () use ($dat_mua_trang_phuc) {
            $dat_mua_trang_phuc->load('nhaCungCap:id,ma_nha_cung_cap,ten_nha_cung_cap');

            return response()->json($dat_mua_trang_phuc);

        }, 'lấy chi tiết đặt mua trang phục');
    }

    /**
     * Tạo đơn đặt mua trang phục mới.
     */
    public function store(Request $request): JsonResponse
    {
        return $this->handleApi(function () use ($request) {
            $validated = $this->validatePayload($request);
            $validated = $this->applyCalculatedFields($validated);
            $validated['trang_thai'] = 'cho_duyet';

            $datMua = DatMuaTrangPhuc::create($validated);
            $datMua->load('nhaCungCap:id,ma_nha_cung_cap,ten_nha_cung_cap');

            return response()->json($datMua, 201);

        }, 'tạo đặt mua trang phục');
    }

    /**
     * Cập nhật đơn đặt mua trang phục.
     */
    public function update(Request $request, DatMuaTrangPhuc $dat_mua_trang_phuc): JsonResponse
    {
        return $this->handleApi(function () use ($request, $dat_mua_trang_phuc) {
            $validated = $this->validatePayload($request);
            $validated = $this->applyCalculatedFields($validated);
            unset($validated['trang_thai']);

            $dat_mua_trang_phuc->update($validated);

            return response()->json($dat_mua_trang_phuc->fresh()->load('nhaCungCap:id,ma_nha_cung_cap,ten_nha_cung_cap'));

        }, 'cập nhật đặt mua trang phục');
    }

    /**
     * Duyệt đơn đặt mua trang phục.
     */
    public function duyet(DatMuaTrangPhuc $dat_mua_trang_phuc): JsonResponse
    {
        return $this->handleApi(function () use ($dat_mua_trang_phuc) {
            if ($dat_mua_trang_phuc->trang_thai !== 'cho_duyet') {
                throw ValidationException::withMessages([
                    'trang_thai' => ['Chỉ có thể duyệt đơn đang chờ duyệt.'],
                ]);
            }

            $dat_mua_trang_phuc->update(['trang_thai' => 'da_duyet']);

            return response()->json(
                $dat_mua_trang_phuc->fresh()->load('nhaCungCap:id,ma_nha_cung_cap,ten_nha_cung_cap')
            );
        }, 'duyệt đơn đặt mua trang phục');
    }

    /**
     * Hủy duyệt đơn đặt mua trang phục.
     */
    public function huyDuyet(DatMuaTrangPhuc $dat_mua_trang_phuc): JsonResponse
    {
        return $this->handleApi(function () use ($dat_mua_trang_phuc) {
            if ($dat_mua_trang_phuc->trang_thai !== 'cho_duyet') {
                throw ValidationException::withMessages([
                    'trang_thai' => ['Chỉ có thể hủy duyệt đơn đang chờ duyệt.'],
                ]);
            }

            $dat_mua_trang_phuc->update(['trang_thai' => 'huy_duyet']);

            return response()->json(
                $dat_mua_trang_phuc->fresh()->load('nhaCungCap:id,ma_nha_cung_cap,ten_nha_cung_cap')
            );
        }, 'hủy duyệt đơn đặt mua trang phục');
    }

    /**
     * Duyệt nhiều đơn đặt mua trang phục đang chờ duyệt.
     */
    public function bulkDuyet(Request $request): JsonResponse
    {
        return $this->handleApi(function () use ($request) {
            $validated = $request->validate([
                'ids' => ['required', 'array', 'min:1'],
                'ids.*' => ['integer', 'exists:dat_mua_trang_phuc,id'],
            ]);

            $updated = DatMuaTrangPhuc::query()
                ->whereIn('id', $validated['ids'])
                ->where('trang_thai', 'cho_duyet')
                ->update(['trang_thai' => 'da_duyet']);

            return response()->json([
                'message' => "Đã duyệt {$updated} đơn đặt mua trang phục.",
                'updated' => $updated,
            ]);
        }, 'duyệt nhiều đơn đặt mua trang phục');
    }

    /**
     * Hủy duyệt nhiều đơn đặt mua trang phục đang chờ duyệt.
     */
    public function bulkHuyDuyet(Request $request): JsonResponse
    {
        return $this->handleApi(function () use ($request) {
            $validated = $request->validate([
                'ids' => ['required', 'array', 'min:1'],
                'ids.*' => ['integer', 'exists:dat_mua_trang_phuc,id'],
            ]);

            $updated = DatMuaTrangPhuc::query()
                ->whereIn('id', $validated['ids'])
                ->where('trang_thai', 'cho_duyet')
                ->update(['trang_thai' => 'huy_duyet']);

            return response()->json([
                'message' => "Đã hủy duyệt {$updated} đơn đặt mua trang phục.",
                'updated' => $updated,
            ]);
        }, 'hủy duyệt nhiều đơn đặt mua trang phục');
    }

    /**
     * Xóa đơn đặt mua trang phục.
     */
    public function destroy(DatMuaTrangPhuc $dat_mua_trang_phuc): JsonResponse
    {
        return $this->handleApi(function () use ($dat_mua_trang_phuc) {
            $dat_mua_trang_phuc->delete();

            return response()->json(['message' => 'Đã xóa đơn đặt mua trang phục.']);

        }, 'xóa đặt mua trang phục');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatePayload(Request $request): array
    {
        return $request->validate([
            'nha_cung_cap_id' => ['required', 'integer', 'exists:nha_cung_cap_trang_phuc,id'],
            'loai_don_hang' => ['required', 'string', Rule::in(['dau_tu_tai_san', 'vat_tu_tieu_hao'])],
            'nguon_hang_hoa' => ['required', 'string', Rule::in(['trong_nuoc', 'nhap_khau'])],
            'ngay_dat' => ['required', 'date'],
            'mat_hang' => ['required', 'array', 'min:1'],
            'mat_hang.*.ten_mat_hang' => ['required', 'string', 'max:255'],
            'mat_hang.*.so_luong' => ['required', 'integer', 'min:1'],
            'mat_hang.*.don_gia' => ['required', 'integer', 'min:0'],
            'mat_hang.*.thanh_tien' => ['required', 'integer', 'min:0'],
            'phi_van_chuyen' => ['nullable', 'integer', 'min:0'],
            'tien_coc' => ['nullable', 'integer', 'min:0'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function applyCalculatedFields(array $validated): array
    {
        $matHang = collect($validated['mat_hang'])->map(function (array $item) {
            $soLuong = (int) $item['so_luong'];
            $donGia = (int) $item['don_gia'];

            return [
                'ten_mat_hang' => trim((string) $item['ten_mat_hang']),
                'so_luong' => $soLuong,
                'don_gia' => $donGia,
                'thanh_tien' => $soLuong * $donGia,
            ];
        })->values()->all();

        $tongTienHang = collect($matHang)->sum('thanh_tien');
        $phiVanChuyen = (int) ($validated['phi_van_chuyen'] ?? 0);
        $tienCoc = (int) ($validated['tien_coc'] ?? 0);

        $validated['mat_hang'] = $matHang;
        $validated['tong_tien_hang'] = $tongTienHang;
        $validated['phi_van_chuyen'] = $phiVanChuyen;
        $validated['tien_coc'] = $tienCoc;
        $validated['du_no'] = $tongTienHang + $phiVanChuyen - $tienCoc;

        return $validated;
    }
}
