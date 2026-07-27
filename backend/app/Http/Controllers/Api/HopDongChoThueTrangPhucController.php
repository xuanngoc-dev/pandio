<?php

namespace App\Http\Controllers\Api;

use App\Models\HopDongChoThueTrangPhuc;
use App\Models\HopDongChoThueTrangPhucSanPhamChoThue;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class HopDongChoThueTrangPhucController extends BaseApiController
{
    /**
     * Danh sách hợp đồng cho thuê trang phục — phân trang + tìm kiếm.
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
                'trang_thai' => ['sometimes', 'nullable', 'string', 'max:50'],
            ]);

            $perPage = $validated['per_page'] ?? 10;
            $keyword = trim((string) ($validated['keyword'] ?? ''));
            $trangThai = $validated['trang_thai'] ?? null;

            $query = HopDongChoThueTrangPhuc::query()
                ->with([
                    'nguoiChoThueUser:id,name,phone',
                    'sanPhamChoThue.sanPham:id,ma_san_pham,ten_san_pham,gia_cho_thue,hinh_anh',
                ])
                ->when($keyword !== '', function ($q) use ($keyword) {
                    $q->where(function ($inner) use ($keyword) {
                        $inner->where('ma_hop_dong', 'like', "%{$keyword}%")
                            ->orWhere('ten_khach_hang', 'like', "%{$keyword}%")
                            ->orWhere('sdt_khach_hang', 'like', "%{$keyword}%");
                    });
                })
                ->when($trangThai, fn ($q) => $q->where('trang_thai', $trangThai))
                ->orderByDesc('ngay_thue')
                ->orderByDesc('id');

            return response()->json($query->paginate($perPage));

        }, 'lấy danh sách hợp đồng cho thuê trang phục');
    }

    /**
     * Chi tiết một hợp đồng cho thuê trang phục.
     */
    public function show(HopDongChoThueTrangPhuc $hop_dong_cho_thue_trang_phuc): JsonResponse
    {
        return $this->handleApi(function () use ($hop_dong_cho_thue_trang_phuc) {
            $hop_dong_cho_thue_trang_phuc->load([
                'nguoiChoThueUser:id,name,phone',
                'sanPhamChoThue.sanPham:id,ma_san_pham,ten_san_pham,gia_cho_thue,hinh_anh',
            ]);

            return response()->json($hop_dong_cho_thue_trang_phuc);

        }, 'lấy chi tiết hợp đồng cho thuê trang phục');
    }

    /**
     * Tạo hợp đồng cho thuê trang phục mới.
     */
    public function store(Request $request): JsonResponse
    {
        return $this->handleApi(function () use ($request) {
            $validated = $this->validatePayload($request);
            $validated = $this->applyCalculatedFields($validated);
            $validated['nguoi_cho_thue'] = $request->user()->id;
            $sanPhamItems = $validated['san_pham_cho_thue'];
            unset($validated['san_pham_cho_thue']);

            $hopDong = DB::transaction(function () use ($validated, $sanPhamItems) {
                $hopDong = HopDongChoThueTrangPhuc::create($validated);
                $this->syncSanPhamChoThue($hopDong, $sanPhamItems);

                return $hopDong;
            });

            $hopDong->load([
                'nguoiChoThueUser:id,name,phone',
                'sanPhamChoThue.sanPham:id,ma_san_pham,ten_san_pham,gia_cho_thue,hinh_anh',
            ]);

            return response()->json($hopDong, 201);

        }, 'tạo hợp đồng cho thuê trang phục');
    }

    /**
     * Cập nhật hợp đồng cho thuê trang phục.
     */
    public function update(Request $request, HopDongChoThueTrangPhuc $hop_dong_cho_thue_trang_phuc): JsonResponse
    {
        return $this->handleApi(function () use ($request, $hop_dong_cho_thue_trang_phuc) {
            $validated = $this->validatePayload($request, $hop_dong_cho_thue_trang_phuc->id);
            $validated = $this->applyCalculatedFields($validated);
            unset($validated['nguoi_cho_thue']);
            $sanPhamItems = $validated['san_pham_cho_thue'];
            unset($validated['san_pham_cho_thue']);

            DB::transaction(function () use ($hop_dong_cho_thue_trang_phuc, $validated, $sanPhamItems) {
                $hop_dong_cho_thue_trang_phuc->update($validated);
                $this->syncSanPhamChoThue($hop_dong_cho_thue_trang_phuc, $sanPhamItems);
            });

            return response()->json($hop_dong_cho_thue_trang_phuc->fresh()->load([
                'nguoiChoThueUser:id,name,phone',
                'sanPhamChoThue.sanPham:id,ma_san_pham,ten_san_pham,gia_cho_thue,hinh_anh',
            ]));

        }, 'cập nhật hợp đồng cho thuê trang phục');
    }

    /**
     * Xóa hợp đồng cho thuê trang phục.
     */
    public function destroy(HopDongChoThueTrangPhuc $hop_dong_cho_thue_trang_phuc): JsonResponse
    {
        return $this->handleApi(function () use ($hop_dong_cho_thue_trang_phuc) {
            $hop_dong_cho_thue_trang_phuc->delete();

            return response()->json(['message' => 'Đã xóa hợp đồng cho thuê trang phục.']);

        }, 'xóa hợp đồng cho thuê trang phục');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatePayload(Request $request, ?int $ignoreId = null): array
    {
        $maHopDongRule = Rule::unique('hop_dong_cho_thue_trang_phuc', 'ma_hop_dong');
        if ($ignoreId !== null) {
            $maHopDongRule = $maHopDongRule->ignore($ignoreId);
        }

        return $request->validate([
            'ma_hop_dong' => ['required', 'string', 'max:100', $maHopDongRule],
            'ten_khach_hang' => ['required', 'string', 'max:255'],
            'sdt_khach_hang' => ['required', 'string', 'max:20'],
            'ngay_thue' => ['required', 'date'],
            'ngay_tra_du_kien' => ['required', 'date', 'after_or_equal:ngay_thue'],
            'ngay_tra_chinh_thuc' => ['nullable', 'date', 'after_or_equal:ngay_thue'],
            'tong_tien' => ['nullable', 'integer', 'min:0'],
            'giam_gia' => ['nullable', 'integer', 'min:0'],
            'tien_coc' => ['nullable', 'integer', 'min:0'],
            'trang_thai' => ['required', 'string', Rule::in([
                'cho_xac_nhan',
                'dang_thue',
                'da_tra',
                'qua_han',
                'da_huy',
            ])],
            'nguoi_cho_thue' => ['nullable', 'integer', 'exists:users,id'],
            'nguoi_tham_gia' => ['nullable', 'array'],
            'nguoi_tham_gia.*' => ['integer', 'exists:users,id'],
            'ghi_chu_sale' => ['nullable', 'string'],
            'ghi_chu_khach' => ['nullable', 'string'],
            'san_pham_cho_thue' => ['required', 'array', 'min:1'],
            'san_pham_cho_thue.*.san_pham_id' => ['required', 'integer', 'exists:trang_phuc,id', 'distinct'],
            'san_pham_cho_thue.*.ghi_chu' => ['nullable', 'string'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function applyCalculatedFields(array $validated): array
    {
        $ngayThue = Carbon::parse($validated['ngay_thue'])->startOfDay();
        $ngayTraDuKien = Carbon::parse($validated['ngay_tra_du_kien'])->startOfDay();

        $validated['so_ngay_thue'] = max(1, $ngayThue->diffInDays($ngayTraDuKien) + 1);
        $validated['tong_tien'] = (int) ($validated['tong_tien'] ?? 0);
        $validated['giam_gia'] = (int) ($validated['giam_gia'] ?? 0);
        $validated['tien_coc'] = (int) ($validated['tien_coc'] ?? 0);
        $validated['nguoi_tham_gia'] = collect($validated['nguoi_tham_gia'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $validated['san_pham_cho_thue'] = collect($validated['san_pham_cho_thue'])
            ->map(fn (array $item) => [
                'san_pham_id' => (int) $item['san_pham_id'],
                'ghi_chu' => isset($item['ghi_chu']) ? trim((string) $item['ghi_chu']) : null,
            ])
            ->values()
            ->all();

        return $validated;
    }

    /**
     * @param  array<int, array{san_pham_id: int, ghi_chu: ?string}>  $sanPhamItems
     */
    private function syncSanPhamChoThue(HopDongChoThueTrangPhuc $hopDong, array $sanPhamItems): void
    {
        $hopDong->sanPhamChoThue()->delete();

        foreach ($sanPhamItems as $item) {
            HopDongChoThueTrangPhucSanPhamChoThue::create([
                'hop_dong_id' => $hopDong->id,
                'san_pham_id' => $item['san_pham_id'],
                'ghi_chu' => $item['ghi_chu'] ?: null,
            ]);
        }
    }
}
