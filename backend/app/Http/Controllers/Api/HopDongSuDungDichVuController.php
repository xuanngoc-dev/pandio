<?php

namespace App\Http\Controllers\Api;

use App\Models\HopDongDongSddvCombo;
use App\Models\HopDongDongSddvConcept;
use App\Models\HopDongDongSddvDichVu;
use App\Models\HopDongDongSddvTrangPhuc;
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
                'loai_hop_dong_id' => ['sometimes', 'nullable', 'integer', 'exists:danh_muc_loai_hop_dong,id'],
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
                ->with($this->detailRelations())
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
            return response()->json($this->loadDetail($hop_dong_su_dung_dich_vu));

        }, 'lấy chi tiết hợp đồng sử dụng dịch vụ');
    }

    /**
     * Công việc điều phối của user đang đăng nhập.
     * Lọc hop_dong_su_dung_dich_vu có id user nằm trong
     * thong_tin_dieu_phoi.{quay_phim|tho_make|tho_edit|tho_chup}.gia_tri
     *
     * Query: page, per_page, ket_qua_trang_thai, keyword, loai_hop_dong_id,
     * ngay_chup, ngay_tra_demo, ngay_tra_chinh_thuc
     * (cho_nhan = gia_tri null/rỗng; các tab khác = đúng giá trị)
     */
    public function congViecCuaToi(Request $request): JsonResponse
    {
        return $this->handleApi(function () use ($request) {
            $user = $request->user();
            if (! $user) {
                abort(401, 'Unauthenticated.');
            }

            $userId = (int) $user->id;
            $validated = $request->validate([
                'page' => ['sometimes', 'integer', 'min:1'],
                'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
                'ket_qua_trang_thai' => ['sometimes', 'nullable', 'string', Rule::in([
                    'cho_nhan',
                    'dang_xu_ly',
                    'gui_khach_kiem_tra',
                    'san_xuat_in_an',
                    'cho_nghiem_thu',
                    'hoan_thanh',
                ])],
                'keyword' => ['sometimes', 'nullable', 'string', 'max:255'],
                'loai_hop_dong_id' => ['sometimes', 'nullable', 'integer', 'exists:danh_muc_loai_hop_dong,id'],
                'ngay_chup' => ['sometimes', 'nullable', 'date'],
                'ngay_tra_demo' => ['sometimes', 'nullable', 'date'],
                'ngay_tra_chinh_thuc' => ['sometimes', 'nullable', 'date'],
            ]);
            $perPage = $validated['per_page'] ?? 24;
            $ketQuaTrangThai = $validated['ket_qua_trang_thai'] ?? 'cho_nhan';

            $baseQuery = $this->congViecCuaToiBaseQuery($userId);
            $this->applyCongViecDieuPhoiListFilters($baseQuery, $validated);
            $query = (clone $baseQuery)
                ->with(['loaiHopDong:id,ten_hop_dong,ma_hop_dong']);
            $this->applyKetQuaTrangThaiFilter($query, $ketQuaTrangThai);
            $query->orderByDesc('id');

            $paginator = $query->paginate($perPage);
            $payload = $paginator->toArray();
            $payload['tab_counts'] = $this->congViecTabCounts($baseQuery);

            return response()->json($payload);
        }, 'lấy danh sách công việc điều phối của tôi');
    }

    /**
     * Nhân viên nhận công việc điều phối → ket_qua_hop_dong.trang_thai = dang_xu_ly.
     */
    public function nhanCongViec(Request $request, HopDongSuDungDichVu $hop_dong_su_dung_dich_vu): JsonResponse
    {
        return $this->handleApi(function () use ($request, $hop_dong_su_dung_dich_vu) {
            $user = $request->user();
            if (! $user) {
                abort(401, 'Unauthenticated.');
            }

            $userId = (int) $user->id;
            $assigned = $this->congViecCuaToiBaseQuery($userId)
                ->where('hop_dong_su_dung_dich_vu.id', $hop_dong_su_dung_dich_vu->id)
                ->exists();

            if (! $assigned) {
                abort(403, 'Bạn không được gán vào công việc điều phối này.');
            }

            $ketQua = is_array($hop_dong_su_dung_dich_vu->ket_qua_hop_dong)
                ? $hop_dong_su_dung_dich_vu->ket_qua_hop_dong
                : HopDongSuDungDichVu::defaultKetQuaHopDong();

            $defaults = HopDongSuDungDichVu::defaultKetQuaHopDong();
            foreach ($defaults as $key => $defaultField) {
                if (! isset($ketQua[$key]) || ! is_array($ketQua[$key])) {
                    $ketQua[$key] = $defaultField;
                }
            }

            $current = $ketQua['trang_thai']['gia_tri'] ?? null;
            if ($current !== null && $current !== '') {
                abort(422, 'Công việc đã được nhận.');
            }

            $ketQua['trang_thai'] = array_merge(
                $defaults['trang_thai'],
                is_array($ketQua['trang_thai'] ?? null) ? $ketQua['trang_thai'] : [],
                ['gia_tri' => 'dang_xu_ly']
            );

            $hop_dong_su_dung_dich_vu->update(['ket_qua_hop_dong' => $ketQua]);

            return response()->json(
                $hop_dong_su_dung_dich_vu->fresh()->load(['loaiHopDong:id,ten_hop_dong,ma_hop_dong'])
            );
        }, 'nhận công việc điều phối');
    }

    /**
     * Cập nhật một field trong ket_qua_hop_dong (vd. link_file_demo, link_file_goc).
     */
    public function capNhatKetQuaHopDong(Request $request, HopDongSuDungDichVu $hop_dong_su_dung_dich_vu): JsonResponse
    {
        return $this->handleApi(function () use ($request, $hop_dong_su_dung_dich_vu) {
            $user = $request->user();
            if (! $user) {
                abort(401, 'Unauthenticated.');
            }

            $userId = (int) $user->id;
            $assigned = $this->congViecCuaToiBaseQuery($userId)
                ->where('hop_dong_su_dung_dich_vu.id', $hop_dong_su_dung_dich_vu->id)
                ->exists();

            if (! $assigned) {
                abort(403, 'Bạn không được gán vào công việc điều phối này.');
            }

            $allowedKeys = array_keys(HopDongSuDungDichVu::defaultKetQuaHopDong());
            $validated = $request->validate([
                'key' => ['required', 'string', Rule::in($allowedKeys)],
                'gia_tri' => ['required', 'string', 'max:2048'],
            ]);

            $key = $validated['key'];
            if ($key === 'trang_thai') {
                abort(422, 'Không thể cập nhật trạng thái qua endpoint này.');
            }

            $giaTri = trim($validated['gia_tri']);
            if ($giaTri === '') {
                abort(422, 'Link không được để trống.');
            }

            $defaults = HopDongSuDungDichVu::defaultKetQuaHopDong();
            $ketQua = is_array($hop_dong_su_dung_dich_vu->ket_qua_hop_dong)
                ? $hop_dong_su_dung_dich_vu->ket_qua_hop_dong
                : $defaults;

            foreach ($defaults as $fieldKey => $defaultField) {
                if (! isset($ketQua[$fieldKey]) || ! is_array($ketQua[$fieldKey])) {
                    $ketQua[$fieldKey] = $defaultField;
                }
            }

            $ketQua[$key] = array_merge(
                $defaults[$key],
                is_array($ketQua[$key] ?? null) ? $ketQua[$key] : [],
                ['gia_tri' => $giaTri]
            );

            $hop_dong_su_dung_dich_vu->update(['ket_qua_hop_dong' => $ketQua]);

            return response()->json(
                $hop_dong_su_dung_dich_vu->fresh()->load(['loaiHopDong:id,ten_hop_dong,ma_hop_dong'])
            );
        }, 'cập nhật kết quả hợp đồng');
    }

    /**
     * Gửi khách kiểm tra → ket_qua_hop_dong.trang_thai = gui_khach_kiem_tra.
     * Yêu cầu đã có link_file_goc và link_file_demo.
     */
    public function guiKhachKiemTra(Request $request, HopDongSuDungDichVu $hop_dong_su_dung_dich_vu): JsonResponse
    {
        return $this->handleApi(function () use ($request, $hop_dong_su_dung_dich_vu) {
            $user = $request->user();
            if (! $user) {
                abort(401, 'Unauthenticated.');
            }

            $userId = (int) $user->id;
            $assigned = $this->congViecCuaToiBaseQuery($userId)
                ->where('hop_dong_su_dung_dich_vu.id', $hop_dong_su_dung_dich_vu->id)
                ->exists();

            if (! $assigned) {
                abort(403, 'Bạn không được gán vào công việc điều phối này.');
            }

            $defaults = HopDongSuDungDichVu::defaultKetQuaHopDong();
            $ketQua = is_array($hop_dong_su_dung_dich_vu->ket_qua_hop_dong)
                ? $hop_dong_su_dung_dich_vu->ket_qua_hop_dong
                : $defaults;

            foreach ($defaults as $fieldKey => $defaultField) {
                if (! isset($ketQua[$fieldKey]) || ! is_array($ketQua[$fieldKey])) {
                    $ketQua[$fieldKey] = $defaultField;
                }
            }

            $current = $ketQua['trang_thai']['gia_tri'] ?? null;
            if ($current !== 'dang_xu_ly') {
                abort(422, 'Chỉ có thể gửi khách kiểm tra khi công việc đang xử lý.');
            }

            $linkGoc = trim((string) ($ketQua['link_file_goc']['gia_tri'] ?? ''));
            $linkDemo = trim((string) ($ketQua['link_file_demo']['gia_tri'] ?? ''));
            if ($linkGoc === '' || $linkDemo === '') {
                abort(422, 'Cần có đủ File gốc và File demo trước khi gửi khách kiểm tra.');
            }

            $ketQua['trang_thai'] = array_merge(
                $defaults['trang_thai'],
                is_array($ketQua['trang_thai'] ?? null) ? $ketQua['trang_thai'] : [],
                ['gia_tri' => 'gui_khach_kiem_tra']
            );

            $hop_dong_su_dung_dich_vu->update(['ket_qua_hop_dong' => $ketQua]);

            return response()->json(
                $hop_dong_su_dung_dich_vu->fresh()->load(['loaiHopDong:id,ten_hop_dong,ma_hop_dong'])
            );
        }, 'gửi khách kiểm tra');
    }

    /**
     * Xử lý phản hồi khách sau khi gửi kiểm tra.
     * dong_y → san_xuat_in_an; khong_dong_y → dang_xu_ly.
     */
    public function xuLyKhachKiemTra(Request $request, HopDongSuDungDichVu $hop_dong_su_dung_dich_vu): JsonResponse
    {
        return $this->handleApi(function () use ($request, $hop_dong_su_dung_dich_vu) {
            $user = $request->user();
            if (! $user) {
                abort(401, 'Unauthenticated.');
            }

            $userId = (int) $user->id;
            $assigned = $this->congViecCuaToiBaseQuery($userId)
                ->where('hop_dong_su_dung_dich_vu.id', $hop_dong_su_dung_dich_vu->id)
                ->exists();

            if (! $assigned) {
                abort(403, 'Bạn không được gán vào công việc điều phối này.');
            }

            $validated = $request->validate([
                'ket_qua' => ['required', 'string', Rule::in(['dong_y', 'khong_dong_y'])],
                'y_kien_khach_hang' => [
                    'required_if:ket_qua,khong_dong_y',
                    'nullable',
                    'string',
                    'max:5000',
                ],
            ]);

            $defaults = HopDongSuDungDichVu::defaultKetQuaHopDong();
            $ketQua = is_array($hop_dong_su_dung_dich_vu->ket_qua_hop_dong)
                ? $hop_dong_su_dung_dich_vu->ket_qua_hop_dong
                : $defaults;

            foreach ($defaults as $fieldKey => $defaultField) {
                if (! isset($ketQua[$fieldKey]) || ! is_array($ketQua[$fieldKey])) {
                    $ketQua[$fieldKey] = $defaultField;
                }
            }

            $current = $ketQua['trang_thai']['gia_tri'] ?? null;
            if ($current !== 'gui_khach_kiem_tra') {
                abort(422, 'Chỉ có thể xử lý khi công việc đang ở bước Gửi khách kiểm tra.');
            }

            $isDongY = $validated['ket_qua'] === 'dong_y';
            $nextStatus = $isDongY ? 'san_xuat_in_an' : 'dang_xu_ly';

            if (! $isDongY) {
                $yKien = trim((string) ($validated['y_kien_khach_hang'] ?? ''));
                if ($yKien === '') {
                    abort(422, 'Vui lòng nhập ý kiến khách hàng.');
                }

                $ketQua['y_kien_khach_hang'] = array_merge(
                    $defaults['y_kien_khach_hang'],
                    is_array($ketQua['y_kien_khach_hang'] ?? null) ? $ketQua['y_kien_khach_hang'] : [],
                    ['gia_tri' => $yKien]
                );
            }

            $ketQua['trang_thai'] = array_merge(
                $defaults['trang_thai'],
                is_array($ketQua['trang_thai'] ?? null) ? $ketQua['trang_thai'] : [],
                ['gia_tri' => $nextStatus]
            );

            $hop_dong_su_dung_dich_vu->update(['ket_qua_hop_dong' => $ketQua]);

            return response()->json(
                $hop_dong_su_dung_dich_vu->fresh()->load(['loaiHopDong:id,ten_hop_dong,ma_hop_dong'])
            );
        }, 'xử lý phản hồi khách kiểm tra');
    }

    /**
     * Bàn giao sản phẩm → ket_qua_hop_dong.trang_thai = cho_nghiem_thu.
     * Yêu cầu đã có link_giao_san_pham (file chính thức).
     */
    public function banGiao(Request $request, HopDongSuDungDichVu $hop_dong_su_dung_dich_vu): JsonResponse
    {
        return $this->handleApi(function () use ($request, $hop_dong_su_dung_dich_vu) {
            $user = $request->user();
            if (! $user) {
                abort(401, 'Unauthenticated.');
            }

            $userId = (int) $user->id;
            $assigned = $this->congViecCuaToiBaseQuery($userId)
                ->where('hop_dong_su_dung_dich_vu.id', $hop_dong_su_dung_dich_vu->id)
                ->exists();

            if (! $assigned) {
                abort(403, 'Bạn không được gán vào công việc điều phối này.');
            }

            $defaults = HopDongSuDungDichVu::defaultKetQuaHopDong();
            $ketQua = is_array($hop_dong_su_dung_dich_vu->ket_qua_hop_dong)
                ? $hop_dong_su_dung_dich_vu->ket_qua_hop_dong
                : $defaults;

            foreach ($defaults as $fieldKey => $defaultField) {
                if (! isset($ketQua[$fieldKey]) || ! is_array($ketQua[$fieldKey])) {
                    $ketQua[$fieldKey] = $defaultField;
                }
            }

            $current = $ketQua['trang_thai']['gia_tri'] ?? null;
            if ($current !== 'san_xuat_in_an') {
                abort(422, 'Chỉ có thể bàn giao khi công việc đang ở bước Sản xuất & in ấn.');
            }

            $linkChinhThuc = trim((string) ($ketQua['link_giao_san_pham']['gia_tri'] ?? ''));
            if ($linkChinhThuc === '') {
                abort(422, 'Cần có File chính thức trước khi bàn giao.');
            }

            $ketQua['trang_thai'] = array_merge(
                $defaults['trang_thai'],
                is_array($ketQua['trang_thai'] ?? null) ? $ketQua['trang_thai'] : [],
                ['gia_tri' => 'cho_nghiem_thu']
            );

            $hop_dong_su_dung_dich_vu->update(['ket_qua_hop_dong' => $ketQua]);

            return response()->json(
                $hop_dong_su_dung_dich_vu->fresh()->load(['loaiHopDong:id,ten_hop_dong,ma_hop_dong'])
            );
        }, 'bàn giao sản phẩm');
    }

    /**
     * Xử lý nghiệm thu ở bước Nghiệm thu.
     * lam_lai (+ y_kien) → san_xuat_in_an; hoan_thanh → hoan_thanh.
     */
    public function xuLyNghiemThu(Request $request, HopDongSuDungDichVu $hop_dong_su_dung_dich_vu): JsonResponse
    {
        return $this->handleApi(function () use ($request, $hop_dong_su_dung_dich_vu) {
            $user = $request->user();
            if (! $user) {
                abort(401, 'Unauthenticated.');
            }

            $userId = (int) $user->id;
            $assigned = $this->congViecCuaToiBaseQuery($userId)
                ->where('hop_dong_su_dung_dich_vu.id', $hop_dong_su_dung_dich_vu->id)
                ->exists();

            if (! $assigned) {
                abort(403, 'Bạn không được gán vào công việc điều phối này.');
            }

            $validated = $request->validate([
                'hanh_dong' => ['required', 'string', Rule::in(['lam_lai', 'hoan_thanh'])],
                'y_kien_khach_hang' => [
                    'required_if:hanh_dong,lam_lai',
                    'nullable',
                    'string',
                    'max:5000',
                ],
            ]);

            $defaults = HopDongSuDungDichVu::defaultKetQuaHopDong();
            $ketQua = is_array($hop_dong_su_dung_dich_vu->ket_qua_hop_dong)
                ? $hop_dong_su_dung_dich_vu->ket_qua_hop_dong
                : $defaults;

            foreach ($defaults as $fieldKey => $defaultField) {
                if (! isset($ketQua[$fieldKey]) || ! is_array($ketQua[$fieldKey])) {
                    $ketQua[$fieldKey] = $defaultField;
                }
            }

            $current = $ketQua['trang_thai']['gia_tri'] ?? null;
            if ($current !== 'cho_nghiem_thu') {
                abort(422, 'Chỉ có thể xử lý khi công việc đang chờ nghiệm thu.');
            }

            if ($validated['hanh_dong'] === 'lam_lai') {
                $yKien = trim((string) ($validated['y_kien_khach_hang'] ?? ''));
                if ($yKien === '') {
                    abort(422, 'Vui lòng nhập ý kiến / yêu cầu của khách hàng.');
                }

                $ketQua['y_kien_khach_hang'] = array_merge(
                    $defaults['y_kien_khach_hang'],
                    is_array($ketQua['y_kien_khach_hang'] ?? null) ? $ketQua['y_kien_khach_hang'] : [],
                    ['gia_tri' => $yKien]
                );

                $nextStatus = 'san_xuat_in_an';
            } else {
                $nextStatus = 'hoan_thanh';
            }

            $ketQua['trang_thai'] = array_merge(
                $defaults['trang_thai'],
                is_array($ketQua['trang_thai'] ?? null) ? $ketQua['trang_thai'] : [],
                ['gia_tri' => $nextStatus]
            );

            $hop_dong_su_dung_dich_vu->update(['ket_qua_hop_dong' => $ketQua]);

            return response()->json(
                $hop_dong_su_dung_dich_vu->fresh()->load(['loaiHopDong:id,ten_hop_dong,ma_hop_dong'])
            );
        }, 'xử lý nghiệm thu');
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

            return response()->json($this->loadDetail($hopDong), 201);

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
            [$combos, $dichVu, $concepts, $trangPhucs] = $this->extractNestedPayload($validated);

            $hopDong = DB::transaction(function () use ($validated, $combos, $dichVu, $concepts, $trangPhucs) {
                $hopDong = HopDongSuDungDichVu::create($validated);
                $this->syncNestedRelations($hopDong, $combos, $dichVu, $concepts, $trangPhucs);

                return $hopDong;
            });

            return response()->json($this->loadDetail($hopDong), 201);

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
            [$combos, $dichVu, $concepts, $trangPhucs] = $this->extractNestedPayload($validated);

            DB::transaction(function () use ($hop_dong_su_dung_dich_vu, $validated, $combos, $dichVu, $concepts, $trangPhucs) {
                if ($validated !== []) {
                    $hop_dong_su_dung_dich_vu->update($validated);
                }
                $this->syncNestedRelations($hop_dong_su_dung_dich_vu, $combos, $dichVu, $concepts, $trangPhucs);
            });

            return response()->json($this->loadDetail($hop_dong_su_dung_dich_vu->fresh()));

        }, 'cập nhật hợp đồng sử dụng dịch vụ');
    }

    /**
     * Ghi nhận thanh toán (lần 2 / lần 3) cho hợp đồng sử dụng dịch vụ.
     */
    public function thanhToan(Request $request, HopDongSuDungDichVu $hop_dong_su_dung_dich_vu): JsonResponse
    {
        return $this->handleApi(function () use ($request, $hop_dong_su_dung_dich_vu) {
            if (! in_array($hop_dong_su_dung_dich_vu->trang_thai, ['da_coc', 'dang_thuc_hien'], true)) {
                return response()->json([
                    'message' => 'Chỉ thanh toán hợp đồng đang thực hiện hoặc đã cọc.',
                ], 422);
            }

            $validated = $request->validate([
                'so_tien_thanh_toan' => ['required', 'integer', 'min:1'],
                'hinh_thuc_thanh_toan' => ['required', 'string', Rule::in(['tien_mat', 'chuyen_khoan'])],
                'ghi_chu_sale' => ['nullable', 'string'],
            ]);

            $tongTien = (int) $hop_dong_su_dung_dich_vu->tong_tien;
            $phatSinh = (int) $hop_dong_su_dung_dich_vu->phat_sinh;
            $chietKhau = (int) $hop_dong_su_dung_dich_vu->chiet_khau;
            $giamGia = (int) $hop_dong_su_dung_dich_vu->khuyen_mai_theo_ma_giam_gia;
            $khachPhaiTra = max(0, $tongTien + $phatSinh - $chietKhau - $giamGia);

            $lan1 = (int) $hop_dong_su_dung_dich_vu->so_tien_thanh_toan_lan_1;
            $lan2 = (int) $hop_dong_su_dung_dich_vu->so_tien_thanh_toan_lan_2;
            $lan3 = (int) $hop_dong_su_dung_dich_vu->so_tien_thanh_toan_lan_3;
            $daThanhToan = $lan1 + $lan2 + $lan3;
            $conLai = max(0, $khachPhaiTra - $daThanhToan);
            $soTien = (int) $validated['so_tien_thanh_toan'];

            if ($conLai <= 0) {
                return response()->json([
                    'message' => 'Hợp đồng đã thanh toán đủ.',
                ], 422);
            }

            $slot = null;
            if ($lan1 <= 0) {
                $slot = 1;
            } elseif ($lan2 <= 0) {
                $slot = 2;
            } elseif ($lan3 <= 0) {
                $slot = 3;
            }

            if ($slot === null) {
                return response()->json([
                    'message' => 'Đã ghi nhận đủ 3 lần thanh toán. Không thể ghi thêm.',
                ], 422);
            }

            // Lần 3: số tiền = khách phải TT − lần 1 − lần 2
            if ($slot === 3) {
                $soTien = max(0, $khachPhaiTra - $lan1 - $lan2);
                if ($soTien <= 0) {
                    return response()->json([
                        'message' => 'Hợp đồng đã thanh toán đủ.',
                    ], 422);
                }
            } elseif ($soTien > $conLai) {
                return response()->json([
                    'message' => 'Số tiền thanh toán không được vượt quá số tiền còn lại.',
                    'errors' => [
                        'so_tien_thanh_toan' => ['Số tiền thanh toán không được vượt quá số tiền còn lại.'],
                    ],
                ], 422);
            }

            $today = now()->toDateString();
            $hinhThucLabel = $validated['hinh_thuc_thanh_toan'] === 'chuyen_khoan'
                ? 'Chuyển khoản'
                : 'Tiền mặt';
            $noteLine = sprintf(
                '[TT lần %d · %s · %s] %s',
                $slot,
                $today,
                $hinhThucLabel,
                number_format($soTien, 0, ',', '.').' đ'
            );
            $userNote = trim((string) ($validated['ghi_chu_sale'] ?? ''));
            if ($userNote !== '') {
                $noteLine .= ' — '.$userNote;
            }
            $existingNote = trim((string) ($hop_dong_su_dung_dich_vu->ghi_chu_sale ?? ''));

            $updateData = [
                "so_tien_thanh_toan_lan_{$slot}" => $soTien,
                "thoi_gian_thanh_toan_lan_{$slot}" => $today,
                'ghi_chu_sale' => $existingNote !== ''
                    ? $existingNote."\n".$noteLine
                    : $noteLine,
            ];

            DB::transaction(function () use ($hop_dong_su_dung_dich_vu, $updateData) {
                $hop_dong_su_dung_dich_vu->update($updateData);
            });

            return response()->json($this->loadDetail($hop_dong_su_dung_dich_vu->fresh()));
        }, 'thanh toán hợp đồng sử dụng dịch vụ');
    }

    /**
     * Đổi trạng thái từ màn vận hành cuối:
     * hủy / tất toán / khách đồng ý|không đồng ý / nghiệm thu hoàn thành|làm lại.
     * Đồng bộ logic với luồng Điều phối tự động (Công việc của tôi), không bắt buộc user được gán.
     */
    public function doiTrangThai(Request $request, HopDongSuDungDichVu $hop_dong_su_dung_dich_vu): JsonResponse
    {
        return $this->handleApi(function () use ($request, $hop_dong_su_dung_dich_vu) {
            $validated = $request->validate([
                'hanh_dong' => [
                    'required',
                    'string',
                    Rule::in([
                        'huy',
                        'tat_toan',
                        'khach_dong_y',
                        'khach_khong_dong_y',
                        'nghiem_thu_hoan_thanh',
                        'nghiem_thu_lam_lai',
                    ]),
                ],
                'y_kien_khach_hang' => [
                    'required_if:hanh_dong,khach_khong_dong_y,nghiem_thu_lam_lai',
                    'nullable',
                    'string',
                    'max:5000',
                ],
                'ly_do' => ['nullable', 'string', 'max:2000'],
            ]);

            $hanhDong = $validated['hanh_dong'];
            $trangThaiHd = $hop_dong_su_dung_dich_vu->trang_thai;
            $ketQua = $this->normalizeKetQuaHopDong($hop_dong_su_dung_dich_vu->ket_qua_hop_dong);
            $ketQuaStatus = $ketQua['trang_thai']['gia_tri'] ?? null;

            if (in_array($hanhDong, ['huy', 'tat_toan'], true)) {
                if (in_array($trangThaiHd, ['da_huy', 'hoan_thanh'], true)) {
                    return response()->json([
                        'message' => 'Hợp đồng đã kết thúc, không thể đổi trạng thái.',
                    ], 422);
                }
            }

            if ($hanhDong === 'huy') {
                $updateData = ['trang_thai' => 'da_huy'];
                $lyDo = trim((string) ($validated['ly_do'] ?? ''));
                if ($lyDo !== '') {
                    $note = '[Hủy HĐ] '.$lyDo;
                    $existing = trim((string) ($hop_dong_su_dung_dich_vu->ghi_chu_sale ?? ''));
                    $updateData['ghi_chu_sale'] = $existing !== '' ? $existing."\n".$note : $note;
                }
                $hop_dong_su_dung_dich_vu->update($updateData);

                return response()->json($this->loadDetail($hop_dong_su_dung_dich_vu->fresh()));
            }

            if ($hanhDong === 'tat_toan') {
                if (! in_array($trangThaiHd, ['da_coc', 'dang_thuc_hien'], true)) {
                    return response()->json([
                        'message' => 'Chỉ tất toán hợp đồng đã cọc / đang thực hiện.',
                    ], 422);
                }

                $conLai = $this->tinhConLaiThanhToan($hop_dong_su_dung_dich_vu);
                if ($conLai > 0) {
                    return response()->json([
                        'message' => 'Hợp đồng còn thiếu thanh toán. Vui lòng thanh toán đủ trước khi tất toán.',
                    ], 422);
                }

                $hop_dong_su_dung_dich_vu->update(['trang_thai' => 'hoan_thanh']);

                return response()->json($this->loadDetail($hop_dong_su_dung_dich_vu->fresh()));
            }

            if (in_array($hanhDong, ['khach_dong_y', 'khach_khong_dong_y'], true)) {
                if ($ketQuaStatus !== 'gui_khach_kiem_tra') {
                    return response()->json([
                        'message' => 'Chỉ xử lý khi điều phối đang ở bước Gửi khách kiểm tra.',
                    ], 422);
                }

                $isDongY = $hanhDong === 'khach_dong_y';
                $nextStatus = $isDongY ? 'san_xuat_in_an' : 'dang_xu_ly';

                if (! $isDongY) {
                    $yKien = trim((string) ($validated['y_kien_khach_hang'] ?? ''));
                    if ($yKien === '') {
                        return response()->json([
                            'message' => 'Vui lòng nhập ý kiến khách hàng.',
                            'errors' => [
                                'y_kien_khach_hang' => ['Vui lòng nhập ý kiến khách hàng.'],
                            ],
                        ], 422);
                    }
                    $ketQua['y_kien_khach_hang'] = array_merge(
                        $ketQua['y_kien_khach_hang'],
                        ['gia_tri' => $yKien]
                    );
                }

                $ketQua['trang_thai'] = array_merge(
                    $ketQua['trang_thai'],
                    ['gia_tri' => $nextStatus]
                );
                $hop_dong_su_dung_dich_vu->update(['ket_qua_hop_dong' => $ketQua]);

                return response()->json($this->loadDetail($hop_dong_su_dung_dich_vu->fresh()));
            }

            // nghiem_thu_hoan_thanh | nghiem_thu_lam_lai
            if ($ketQuaStatus !== 'cho_nghiem_thu') {
                return response()->json([
                    'message' => 'Chỉ xử lý khi điều phối đang chờ nghiệm thu.',
                ], 422);
            }

            if ($hanhDong === 'nghiem_thu_lam_lai') {
                $yKien = trim((string) ($validated['y_kien_khach_hang'] ?? ''));
                if ($yKien === '') {
                    return response()->json([
                        'message' => 'Vui lòng nhập yêu cầu của khách hàng.',
                        'errors' => [
                            'y_kien_khach_hang' => ['Vui lòng nhập yêu cầu của khách hàng.'],
                        ],
                    ], 422);
                }
                $ketQua['y_kien_khach_hang'] = array_merge(
                    $ketQua['y_kien_khach_hang'],
                    ['gia_tri' => $yKien]
                );
                $ketQua['trang_thai'] = array_merge(
                    $ketQua['trang_thai'],
                    ['gia_tri' => 'san_xuat_in_an']
                );
                $hop_dong_su_dung_dich_vu->update(['ket_qua_hop_dong' => $ketQua]);

                return response()->json($this->loadDetail($hop_dong_su_dung_dich_vu->fresh()));
            }

            $ketQua['trang_thai'] = array_merge(
                $ketQua['trang_thai'],
                ['gia_tri' => 'hoan_thanh']
            );
            $hop_dong_su_dung_dich_vu->update(['ket_qua_hop_dong' => $ketQua]);

            return response()->json($this->loadDetail($hop_dong_su_dung_dich_vu->fresh()));
        }, 'đổi trạng thái hợp đồng sử dụng dịch vụ');
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
     * @return array<int, string>
     */
    private function detailRelations(): array
    {
        return [
            'loaiHopDong:id,ten_hop_dong,ma_hop_dong',
            'nguoiTao:id,name,phone',
            'combos.combo:id,ma_nhom,ten_nhom,gia_goc,gia_khuyen_mai',
            'dichVu.dichVu:id,ma_dich_vu,ten_dich_vu,gia_goc,gia_khuyen_mai',
            'concepts.concept:id,ma_concept,ten_concept,dia_diem,hinh_anh,trang_thai',
            'trangPhucs.trangPhuc:id,ma_san_pham,ten_san_pham,gia_cho_thue,hinh_anh,trang_thai',
        ];
    }

    private function loadDetail(HopDongSuDungDichVu $hopDong): HopDongSuDungDichVu
    {
        return $hopDong->load($this->detailRelations());
    }

    /**
     * @param  mixed  $raw
     * @return array<string, array{ten: string, mo_ta: mixed, gia_tri: mixed}>
     */
    private function normalizeKetQuaHopDong(mixed $raw): array
    {
        $defaults = HopDongSuDungDichVu::defaultKetQuaHopDong();
        $ketQua = is_array($raw) ? $raw : $defaults;

        foreach ($defaults as $fieldKey => $defaultField) {
            if (! isset($ketQua[$fieldKey]) || ! is_array($ketQua[$fieldKey])) {
                $ketQua[$fieldKey] = $defaultField;
            } else {
                $ketQua[$fieldKey] = array_merge($defaultField, $ketQua[$fieldKey]);
            }
        }

        return $ketQua;
    }

    private function tinhConLaiThanhToan(HopDongSuDungDichVu $hopDong): int
    {
        $khachPhaiTra = max(
            0,
            (int) $hopDong->tong_tien
            + (int) $hopDong->phat_sinh
            - (int) $hopDong->chiet_khau
            - (int) $hopDong->khuyen_mai_theo_ma_giam_gia
        );
        $daThanhToan = (int) $hopDong->so_tien_thanh_toan_lan_1
            + (int) $hopDong->so_tien_thanh_toan_lan_2
            + (int) $hopDong->so_tien_thanh_toan_lan_3;

        return max(0, $khachPhaiTra - $daThanhToan);
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
            'loai_hop_dong_id' => [$isUpdate ? 'sometimes' : 'required', 'nullable', 'integer', 'exists:danh_muc_loai_hop_dong,id'],
            'ten_khach_hang' => ['nullable', 'string', 'max:255'],
            'sdt_khach_hang' => ['nullable', 'string', 'max:20'],
            'dia_chi' => ['nullable', 'string', 'max:255'],
            'kenh_tiep_can' => ['nullable', 'string', 'max:255'],
            'thong_tin_hop_dong' => ['nullable', 'array'],
            'thong_tin_dieu_phoi' => ['nullable', 'array'],
            'ket_qua_hop_dong' => ['nullable', 'array'],
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
            'phat_sinh' => ['nullable', 'integer', 'min:0'],
            'chiet_khau' => ['nullable', 'integer', 'min:0'],
            'ma_giam_gia' => ['nullable', 'string', 'max:100'],
            'khuyen_mai_theo_ma_giam_gia' => ['nullable', 'integer', 'min:0'],
            'tien_coc' => ['nullable', 'integer', 'min:0'],
            'so_tien_thanh_toan_lan_1' => ['nullable', 'integer', 'min:0'],
            'so_tien_thanh_toan_lan_2' => ['nullable', 'integer', 'min:0'],
            'so_tien_thanh_toan_lan_3' => ['nullable', 'integer', 'min:0'],
            'thoi_gian_thanh_toan_lan_1' => ['nullable', 'date'],
            'thoi_gian_thanh_toan_lan_2' => ['nullable', 'date'],
            'thoi_gian_thanh_toan_lan_3' => ['nullable', 'date'],
            'hinh_thuc_coc' => ['nullable', 'string', Rule::in(['online', 'offline'])],
            'han_thanh_toan_lan_2' => ['nullable', 'date'],
            'han_thanh_toan_lan_3' => ['nullable', 'date'],
            'qua_tang_kem' => ['nullable', 'string', 'max:255'],
            'yeu_cau_dac_biet' => ['nullable', 'string'],
            'ghi_chu_sale' => ['nullable', 'string'],
            'luot_gioi_thieu' => ['nullable', 'string', 'max:255'],
            'combos' => [$isUpdate ? 'sometimes' : 'nullable', 'array'],
            'combos.*.combo_id' => ['required', 'integer', 'exists:dich_vu_danh_sach_dich_nhom_dich_vu,id', 'distinct'],
            'combos.*.so_luong' => ['required', 'integer', 'min:1', 'max:999'],
            'combos.*.thanh_tien' => ['required', 'integer', 'min:0'],
            'combos.*.ghi_chu' => ['nullable', 'string', 'max:100'],
            'dich_vu' => [$isUpdate ? 'sometimes' : 'nullable', 'array'],
            'dich_vu.*.dich_vu_id' => ['required', 'integer', 'exists:dich_vu_danh_sach_dich_vu_le,id', 'distinct'],
            'dich_vu.*.so_luong' => ['required', 'integer', 'min:1', 'max:999'],
            'dich_vu.*.thanh_tien' => ['required', 'integer', 'min:0'],
            'dich_vu.*.ghi_chu' => ['nullable', 'string', 'max:100'],
            'concepts' => [$isUpdate ? 'sometimes' : 'nullable', 'array'],
            'concepts.*.concept_id' => ['required', 'integer', 'exists:concept,id', 'distinct'],
            'trang_phucs' => [$isUpdate ? 'sometimes' : 'nullable', 'array'],
            'trang_phucs.*.trang_phuc_id' => ['required', 'integer', 'exists:trang_phuc,id', 'distinct'],
            'trang_phucs.*.ngay_bat_dau' => ['nullable', 'date'],
            'trang_phucs.*.ngay_ket_thuc' => ['nullable', 'date', 'after_or_equal:trang_phucs.*.ngay_bat_dau'],
        ]);

        foreach ([
            'tong_tien',
            'phat_sinh',
            'chiet_khau',
            'khuyen_mai_theo_ma_giam_gia',
            'tien_coc',
            'so_tien_thanh_toan_lan_1',
            'so_tien_thanh_toan_lan_2',
            'so_tien_thanh_toan_lan_3',
        ] as $moneyField) {
            if (array_key_exists($moneyField, $validated)) {
                $validated[$moneyField] = (int) ($validated[$moneyField] ?? 0);
            }
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
        if (array_key_exists('combos', $validated)) {
            $validated['combos'] = collect($validated['combos'] ?? [])
                ->map(fn ($item) => [
                    'combo_id' => (int) $item['combo_id'],
                    'so_luong' => (int) $item['so_luong'],
                    'thanh_tien' => (int) $item['thanh_tien'],
                    'ghi_chu' => isset($item['ghi_chu']) ? trim((string) $item['ghi_chu']) : null,
                ])
                ->values()
                ->all();
        }
        if (array_key_exists('dich_vu', $validated)) {
            $validated['dich_vu'] = collect($validated['dich_vu'] ?? [])
                ->map(fn ($item) => [
                    'dich_vu_id' => (int) $item['dich_vu_id'],
                    'so_luong' => (int) $item['so_luong'],
                    'thanh_tien' => (int) $item['thanh_tien'],
                    'ghi_chu' => isset($item['ghi_chu']) ? trim((string) $item['ghi_chu']) : null,
                ])
                ->values()
                ->all();
        }
        if (array_key_exists('concepts', $validated)) {
            $validated['concepts'] = collect($validated['concepts'] ?? [])
                ->map(fn ($item) => [
                    'concept_id' => (int) $item['concept_id'],
                ])
                ->values()
                ->all();
        }
        if (array_key_exists('trang_phucs', $validated)) {
            $validated['trang_phucs'] = collect($validated['trang_phucs'] ?? [])
                ->map(fn ($item) => [
                    'trang_phuc_id' => (int) $item['trang_phuc_id'],
                    'ngay_bat_dau' => $item['ngay_bat_dau'] ?? null,
                    'ngay_ket_thuc' => $item['ngay_ket_thuc'] ?? null,
                ])
                ->values()
                ->all();
        }

        return $validated;
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array{0: ?array, 1: ?array, 2: ?array, 3: ?array}
     */
    private function extractNestedPayload(array &$validated): array
    {
        $combos = array_key_exists('combos', $validated) ? $validated['combos'] : null;
        $dichVu = array_key_exists('dich_vu', $validated) ? $validated['dich_vu'] : null;
        $concepts = array_key_exists('concepts', $validated) ? $validated['concepts'] : null;
        $trangPhucs = array_key_exists('trang_phucs', $validated) ? $validated['trang_phucs'] : null;
        unset($validated['combos'], $validated['dich_vu'], $validated['concepts'], $validated['trang_phucs']);

        return [$combos, $dichVu, $concepts, $trangPhucs];
    }

    /**
     * @param  ?array<int, mixed>  $combos
     * @param  ?array<int, mixed>  $dichVu
     * @param  ?array<int, mixed>  $concepts
     * @param  ?array<int, mixed>  $trangPhucs
     */
    private function syncNestedRelations(
        HopDongSuDungDichVu $hopDong,
        ?array $combos,
        ?array $dichVu,
        ?array $concepts,
        ?array $trangPhucs,
    ): void {
        if (is_array($combos)) {
            $this->syncCombos($hopDong, $combos);
        }
        if (is_array($dichVu)) {
            $this->syncDichVu($hopDong, $dichVu);
        }
        if (is_array($concepts)) {
            $this->syncConcepts($hopDong, $concepts);
        }
        if (is_array($trangPhucs)) {
            $this->syncTrangPhucs($hopDong, $trangPhucs);
        }
    }

    /**
     * @param  array<int, array{combo_id: int, so_luong: int, thanh_tien: int, ghi_chu: ?string}>  $items
     */
    private function syncCombos(HopDongSuDungDichVu $hopDong, array $items): void
    {
        $hopDong->combos()->delete();

        foreach ($items as $item) {
            HopDongDongSddvCombo::create([
                'ma_hop_dong_id' => $hopDong->id,
                'combo_id' => $item['combo_id'],
                'so_luong' => $item['so_luong'],
                'thanh_tien' => $item['thanh_tien'],
                'ghi_chu' => $item['ghi_chu'] !== '' ? $item['ghi_chu'] : null,
            ]);
        }
    }

    /**
     * @param  array<int, array{dich_vu_id: int, so_luong: int, thanh_tien: int, ghi_chu: ?string}>  $items
     */
    private function syncDichVu(HopDongSuDungDichVu $hopDong, array $items): void
    {
        $hopDong->dichVu()->delete();

        foreach ($items as $item) {
            HopDongDongSddvDichVu::create([
                'ma_hop_dong_id' => $hopDong->id,
                'dich_vu_id' => $item['dich_vu_id'],
                'so_luong' => $item['so_luong'],
                'thanh_tien' => $item['thanh_tien'],
                'ghi_chu' => $item['ghi_chu'] !== '' ? $item['ghi_chu'] : null,
            ]);
        }
    }

    /**
     * @param  array<int, array{concept_id: int}>  $items
     */
    private function syncConcepts(HopDongSuDungDichVu $hopDong, array $items): void
    {
        $hopDong->concepts()->delete();

        foreach ($items as $item) {
            HopDongDongSddvConcept::create([
                'ma_hop_dong_id' => $hopDong->id,
                'concept_id' => $item['concept_id'],
            ]);
        }
    }

    /**
     * @param  array<int, array{trang_phuc_id: int, ngay_bat_dau: ?string, ngay_ket_thuc: ?string}>  $items
     */
    private function syncTrangPhucs(HopDongSuDungDichVu $hopDong, array $items): void
    {
        $hopDong->trangPhucs()->delete();

        foreach ($items as $item) {
            HopDongDongSddvTrangPhuc::create([
                'ma_hop_dong_id' => $hopDong->id,
                'trang_phuc_id' => $item['trang_phuc_id'],
                'ngay_bat_dau' => $item['ngay_bat_dau'] ?: null,
                'ngay_ket_thuc' => $item['ngay_ket_thuc'] ?: null,
            ]);
        }
    }

    /**
     * Query gốc: hợp đồng gán user vào các field nhân sự điều phối.
     */
    private function congViecCuaToiBaseQuery(int $userId)
    {
        $staffKeys = ['quay_phim', 'tho_make', 'tho_edit', 'tho_chup'];

        return HopDongSuDungDichVu::query()
            ->whereNotIn('trang_thai', ['moi_tao', 'nhap'])
            ->where(function ($q) use ($userId, $staffKeys) {
                foreach ($staffKeys as $key) {
                    $path = "thong_tin_dieu_phoi->{$key}->gia_tri";
                    $q->orWhere(function ($inner) use ($path, $userId) {
                        $inner->whereJsonContains($path, $userId)
                            ->orWhereJsonContains($path, (string) $userId);
                    });
                }
            });
    }

    /**
     * Lọc danh sách công việc điều phối: keyword, loại HĐ, ngày trong thong_tin_dieu_phoi.
     *
     * @param  array<string, mixed>  $filters
     */
    private function applyCongViecDieuPhoiListFilters($query, array $filters): void
    {
        $keyword = trim((string) ($filters['keyword'] ?? ''));
        if ($keyword !== '') {
            $query->where(function ($inner) use ($keyword) {
                $inner->where('ma_hop_dong', 'like', "%{$keyword}%")
                    ->orWhere('ten_khach_hang', 'like', "%{$keyword}%")
                    ->orWhere('sdt_khach_hang', 'like', "%{$keyword}%")
                    ->orWhere('dia_chi', 'like', "%{$keyword}%")
                    ->orWhere('kenh_tiep_can', 'like', "%{$keyword}%")
                    ->orWhere('ma_giam_gia', 'like', "%{$keyword}%")
                    ->orWhere('luot_gioi_thieu', 'like', "%{$keyword}%")
                    ->orWhere('thong_tin_hop_dong', 'like', "%{$keyword}%");
            });
        }

        $loaiHopDongId = $filters['loai_hop_dong_id'] ?? null;
        if ($loaiHopDongId) {
            $query->where('loai_hop_dong_id', $loaiHopDongId);
        }

        $dateFields = ['ngay_chup', 'ngay_tra_demo', 'ngay_tra_chinh_thuc'];
        foreach ($dateFields as $field) {
            $value = $filters[$field] ?? null;
            if ($value === null || $value === '') {
                continue;
            }

            $query->where("thong_tin_dieu_phoi->{$field}->gia_tri", $value);
        }
    }

    /**
     * Lọc theo ket_qua_hop_dong.trang_thai.gia_tri.
     * cho_nhan = null / thiếu / chuỗi rỗng.
     */
    private function applyKetQuaTrangThaiFilter($query, string $ketQuaTrangThai): void
    {
        if ($ketQuaTrangThai === 'cho_nhan') {
            $query->where(function ($q) {
                $q->whereNull('ket_qua_hop_dong')
                    ->orWhereNull('ket_qua_hop_dong->trang_thai')
                    ->orWhereNull('ket_qua_hop_dong->trang_thai->gia_tri')
                    ->orWhere('ket_qua_hop_dong->trang_thai->gia_tri', '')
                    ->orWhereRaw(
                        "JSON_TYPE(JSON_EXTRACT(ket_qua_hop_dong, '$.trang_thai.gia_tri')) = 'NULL'"
                    );
            });

            return;
        }

        $query->where('ket_qua_hop_dong->trang_thai->gia_tri', $ketQuaTrangThai);
    }

    /**
     * @return array<string, int>
     */
    private function congViecTabCounts($baseQuery): array
    {
        $statuses = [
            'cho_nhan',
            'dang_xu_ly',
            'gui_khach_kiem_tra',
            'san_xuat_in_an',
            'cho_nghiem_thu',
            'hoan_thanh',
        ];

        $counts = [];
        foreach ($statuses as $status) {
            $q = clone $baseQuery;
            $this->applyKetQuaTrangThaiFilter($q, $status);
            $counts[$status] = (int) $q->count();
        }

        return $counts;
    }
}
