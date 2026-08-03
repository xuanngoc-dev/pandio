<?php

namespace App\Http\Controllers\Api;

use App\Models\HopDongSuDungDichVu;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class HopDongSuDungDichVuController extends BaseApiController
{
    /**
     * Danh sách hợp đồng sử dụng dịch vụ — phân trang + tìm kiếm.
     *
     * Query: page, per_page, keyword, loai_hop_dong_id, trang_thai, chi_nhap, tu_ngay, den_ngay
     */
    public function index(Request $request): JsonResponse
    {
        return $this->handleApi(function () use ($request) {
            $validated = $request->validate([
                'page' => ['sometimes', 'integer', 'min:1'],
                'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
                'keyword' => ['sometimes', 'nullable', 'string', 'max:255'],
                'loai_hop_dong_id' => ['sometimes', 'nullable', 'integer', 'exists:loai_hop_dong,id'],
                'trang_thai' => ['sometimes', 'nullable', 'string', 'max:50'],
                'chi_nhap' => ['sometimes', 'boolean'],
                'tu_ngay' => ['sometimes', 'nullable', 'date'],
                'den_ngay' => ['sometimes', 'nullable', 'date', 'after_or_equal:tu_ngay'],
            ]);

            $perPage = $validated['per_page'] ?? 10;
            $keyword = trim((string) ($validated['keyword'] ?? ''));
            $loaiHopDongId = $validated['loai_hop_dong_id'] ?? null;
            $trangThai = $validated['trang_thai'] ?? null;
            $chiNhap = $request->boolean('chi_nhap');
            $tuNgay = $validated['tu_ngay'] ?? null;
            $denNgay = $validated['den_ngay'] ?? null;

            $query = HopDongSuDungDichVu::query()
                ->with([
                    'loaiHopDong:id,ten_hop_dong,ma_hop_dong',
                    'nguoiTao:id,name,phone',
                ])
                ->when($keyword !== '', function ($q) use ($keyword) {
                    $q->where(function ($inner) use ($keyword) {
                        $inner->where('ma_hop_dong', 'like', "%{$keyword}%")
                            ->orWhere('ten_khach_hang', 'like', "%{$keyword}%")
                            ->orWhere('sdt_khach_hang', 'like', "%{$keyword}%")
                            ->orWhere('dia_chi', 'like', "%{$keyword}%")
                            ->orWhere('kenh_tiep_can', 'like', "%{$keyword}%")
                            ->orWhere('ma_giam_gia', 'like', "%{$keyword}%")
                            ->orWhere('luot_gioi_thieu', 'like', "%{$keyword}%")
                            ->orWhere('thong_tin_hop_dong', 'like', "%{$keyword}%");
                    });
                })
                ->when($loaiHopDongId, fn ($q) => $q->where('loai_hop_dong_id', $loaiHopDongId))
                ->when($tuNgay, fn ($q) => $q->whereDate('created_at', '>=', $tuNgay))
                ->when($denNgay, fn ($q) => $q->whereDate('created_at', '<=', $denNgay))
                ->when($chiNhap, function ($q) {
                    // Nháp: moi_tao/nhap và đã chọn loại hợp đồng
                    $q->whereIn('trang_thai', ['moi_tao', 'nhap'])
                        ->whereNotNull('loai_hop_dong_id');
                }, function ($q) use ($trangThai) {
                    if ($trangThai) {
                        $q->where('trang_thai', $trangThai);
                    } else {
                        // Mặc định ẩn hợp đồng nháp / mới tạo
                        $q->whereNotIn('trang_thai', ['moi_tao', 'nhap']);
                    }
                })
                ->orderByDesc('id');

            return response()->json($query->paginate($perPage));

        }, 'lấy danh sách hợp đồng sử dụng dịch vụ');
    }

    /**
     * Chi tiết một hợp đồng sử dụng dịch vụ.
     */
    public function show(HopDongSuDungDichVu $hop_dong_su_dung_dich_vu): JsonResponse
    {
        return $this->handleApi(function () use ($hop_dong_su_dung_dich_vu) {
            $hop_dong_su_dung_dich_vu->load([
                'loaiHopDong:id,ten_hop_dong,ma_hop_dong',
                'nguoiTao:id,name,phone',
            ]);

            return response()->json($hop_dong_su_dung_dich_vu);

        }, 'lấy chi tiết hợp đồng sử dụng dịch vụ');
    }

    /**
     * Khởi tạo hợp đồng nháp + sinh mã HDSDDV_DDMMYYYY{id}.
     */
    public function khoiTao(Request $request): JsonResponse
    {
        return $this->handleApi(function () use ($request) {
            $hopDong = DB::transaction(function () use ($request) {
                $hopDong = HopDongSuDungDichVu::create([
                    'ma_hop_dong' => 'TEMP_'.Str::upper(Str::random(16)),
                    'loai_hop_dong_id' => null,
                    'nguoi_tao_id' => $request->user()->id,
                    'trang_thai' => 'moi_tao',
                    'tong_tien' => 0,
                    'chiet_khau' => 0,
                    'khuyen_mai_theo_ma_giam_gia' => 0,
                    'tien_coc' => 0,
                ]);

                $hopDong->update([
                    'ma_hop_dong' => HopDongSuDungDichVu::buildMaHopDong($hopDong->id),
                ]);

                return $hopDong;
            });

            $hopDong->load([
                'loaiHopDong:id,ten_hop_dong,ma_hop_dong',
                'nguoiTao:id,name,phone',
            ]);

            return response()->json($hopDong, 201);

        }, 'khởi tạo hợp đồng sử dụng dịch vụ');
    }

    /**
     * Tạo hợp đồng sử dụng dịch vụ mới.
     */
    public function store(Request $request): JsonResponse
    {
        return $this->handleApi(function () use ($request) {
            $validated = $this->validatePayload($request);
            $validated['nguoi_tao_id'] = $request->user()->id;

            $hopDong = HopDongSuDungDichVu::create($validated);
            $hopDong->load([
                'loaiHopDong:id,ten_hop_dong,ma_hop_dong',
                'nguoiTao:id,name,phone',
            ]);

            return response()->json($hopDong, 201);

        }, 'tạo hợp đồng sử dụng dịch vụ');
    }

    /**
     * Cập nhật hợp đồng sử dụng dịch vụ.
     */
    public function update(Request $request, HopDongSuDungDichVu $hop_dong_su_dung_dich_vu): JsonResponse
    {
        return $this->handleApi(function () use ($request, $hop_dong_su_dung_dich_vu) {
            $validated = $this->validatePayload($request, $hop_dong_su_dung_dich_vu->id, true);
            unset($validated['nguoi_tao_id'], $validated['ma_hop_dong']);

            $hop_dong_su_dung_dich_vu->update($validated);

            return response()->json($hop_dong_su_dung_dich_vu->fresh()->load([
                'loaiHopDong:id,ten_hop_dong,ma_hop_dong',
                'nguoiTao:id,name,phone',
            ]));

        }, 'cập nhật hợp đồng sử dụng dịch vụ');
    }

    /**
     * Xóa hợp đồng sử dụng dịch vụ.
     */
    public function destroy(HopDongSuDungDichVu $hop_dong_su_dung_dich_vu): JsonResponse
    {
        return $this->handleApi(function () use ($hop_dong_su_dung_dich_vu) {
            $hop_dong_su_dung_dich_vu->delete();

            return response()->json(['message' => 'Đã xóa hợp đồng sử dụng dịch vụ.']);

        }, 'xóa hợp đồng sử dụng dịch vụ');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatePayload(Request $request, ?int $ignoreId = null, bool $isUpdate = false): array
    {
        $maHopDongRule = Rule::unique('hop_dong_su_dung_dich_vu', 'ma_hop_dong');
        if ($ignoreId !== null) {
            $maHopDongRule = $maHopDongRule->ignore($ignoreId);
        }

        $validated = $request->validate([
            'ma_hop_dong' => [$isUpdate ? 'sometimes' : 'required', 'string', 'max:100', $maHopDongRule],
            'loai_hop_dong_id' => [$isUpdate ? 'sometimes' : 'required', 'nullable', 'integer', 'exists:loai_hop_dong,id'],
            'ten_khach_hang' => ['nullable', 'string', 'max:255'],
            'sdt_khach_hang' => ['nullable', 'string', 'max:20'],
            'dia_chi' => ['nullable', 'string', 'max:255'],
            'kenh_tiep_can' => ['nullable', 'string', 'max:255'],
            'thong_tin_hop_dong' => ['nullable', 'array'],
            'nguoi_tham_gia_ids' => ['nullable', 'array'],
            'nguoi_tham_gia_ids.*' => ['integer', 'exists:users,id'],
            'trang_thai' => ['sometimes', 'string', Rule::in([
                'moi_tao',
                'nhap',
                'da_coc',
                'dang_thuc_hien',
                'da_huy',
                'hoan_thanh',
            ])],
            'tong_tien' => ['nullable', 'integer', 'min:0'],
            'chiet_khau' => ['nullable', 'integer', 'min:0'],
            'ma_giam_gia' => ['nullable', 'string', 'max:100'],
            'khuyen_mai_theo_ma_giam_gia' => ['nullable', 'integer', 'min:0'],
            'tien_coc' => ['nullable', 'integer', 'min:0'],
            'hinh_thuc_coc' => ['nullable', 'string', Rule::in(['online', 'offline'])],
            'han_thanh_toan_lan_2' => ['nullable', 'date'],
            'han_thanh_toan_lan_3' => ['nullable', 'date'],
            'qua_tang_kem' => ['nullable', 'string', 'max:255'],
            'yeu_cau_dac_biet' => ['nullable', 'string'],
            'ghi_chu_sale' => ['nullable', 'string'],
            'luot_gioi_thieu' => ['nullable', 'string', 'max:255'],
        ]);

        if (array_key_exists('tong_tien', $validated)) {
            $validated['tong_tien'] = (int) ($validated['tong_tien'] ?? 0);
        }
        if (array_key_exists('chiet_khau', $validated)) {
            $validated['chiet_khau'] = (int) ($validated['chiet_khau'] ?? 0);
        }
        if (array_key_exists('khuyen_mai_theo_ma_giam_gia', $validated)) {
            $validated['khuyen_mai_theo_ma_giam_gia'] = (int) ($validated['khuyen_mai_theo_ma_giam_gia'] ?? 0);
        }
        if (array_key_exists('tien_coc', $validated)) {
            $validated['tien_coc'] = (int) ($validated['tien_coc'] ?? 0);
        }
        if (! $isUpdate) {
            $validated['trang_thai'] = $validated['trang_thai'] ?? 'moi_tao';
        }
        if (array_key_exists('nguoi_tham_gia_ids', $validated)) {
            $validated['nguoi_tham_gia_ids'] = collect($validated['nguoi_tham_gia_ids'] ?? [])
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values()
                ->all();
        }

        return $validated;
    }
}
