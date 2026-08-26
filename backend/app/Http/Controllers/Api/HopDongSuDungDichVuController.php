<?php

namespace App\Http\Controllers\Api;

use App\Models\CauHinhJson;
use App\Models\HopDongDongSddvCombo;
use App\Models\HopDongDongSddvConcept;
use App\Models\HopDongDongSddvDichVu;
use App\Models\HopDongDongSddvTrangPhuc;
use App\Models\HopDongSuDungDichVu;
use App\Models\LoaiHopDong;
use Carbon\Carbon;
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
     * Query: page, per_page, keyword, loai_hop_dong_id, trang_thai, chi_nhap, tu_ngay, den_ngay,
     * loai_quay_chup_id, ngay_chup_tu, ngay_chup_den, so_diem_chup,
     * co_tho_chup, co_tho_make, co_quay_phim, co_tho_edit
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
                'loai_quay_chup_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
                'ngay_chup_tu' => ['sometimes', 'nullable', 'date'],
                'ngay_chup_den' => ['sometimes', 'nullable', 'date', 'after_or_equal:ngay_chup_tu'],
                'so_diem_chup' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:3'],
                'co_tho_chup' => ['sometimes', 'nullable', Rule::in(['0', '1', 0, 1])],
                'co_tho_make' => ['sometimes', 'nullable', Rule::in(['0', '1', 0, 1])],
                'co_quay_phim' => ['sometimes', 'nullable', Rule::in(['0', '1', 0, 1])],
                'co_tho_edit' => ['sometimes', 'nullable', Rule::in(['0', '1', 0, 1])],
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
                });

            $this->applyDieuPhoiAdvancedFilters($query, $validated);

            return response()->json($query->orderByDesc('id')->paginate($perPage));

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
     * Lịch chụp-make: danh sách hợp đồng theo khoảng ngày chụp.
     * Ngày/giờ lấy từ từng buổi trong thong_tin_dieu_phoi.danh_sach_buoi_chup.
     * Loại trừ trang_thai: moi_tao, nhap, da_huy.
     * Sắp xếp theo ngay_chup, gio_chup (null xuống cuối), id.
     *
     * Query: tu_ngay, den_ngay
     * Response: { loai_hop_dong: [...], items: [...] }
     */
    public function lichChupMake(Request $request): JsonResponse
    {
        return $this->handleApi(function () use ($request) {
            $validated = $request->validate([
                'tu_ngay' => ['required', 'date'],
                'den_ngay' => ['required', 'date', 'after_or_equal:tu_ngay'],
            ]);

            $tuNgay = Carbon::parse($validated['tu_ngay'])->toDateString();
            $denNgay = Carbon::parse($validated['den_ngay'])->toDateString();

            $loaiHopDongs = LoaiHopDong::query()
                ->where('trang_thai', 'hoat_dong')
                ->orderBy('ten_hop_dong')
                ->get(['id', 'ten_hop_dong', 'ma_hop_dong']);

            $rows = HopDongSuDungDichVu::query()
                ->with(['loaiHopDong:id,ten_hop_dong,ma_hop_dong'])
                ->whereNotIn('hop_dong_su_dung_dich_vu.trang_thai', ['moi_tao', 'nhap', 'da_huy'])
                ->whereNotNull('hop_dong_su_dung_dich_vu.thong_tin_dieu_phoi')
                ->select([
                    'hop_dong_su_dung_dich_vu.id',
                    'hop_dong_su_dung_dich_vu.ma_hop_dong',
                    'hop_dong_su_dung_dich_vu.ten_khach_hang',
                    'hop_dong_su_dung_dich_vu.sdt_khach_hang',
                    'hop_dong_su_dung_dich_vu.loai_hop_dong_id',
                    'hop_dong_su_dung_dich_vu.trang_thai',
                    'hop_dong_su_dung_dich_vu.thong_tin_dieu_phoi',
                ])
                ->get();

            $items = [];
            foreach ($rows as $hd) {
                foreach ($hd->dieuPhoiSessions() as $session) {
                    $ngayChup = substr((string) HopDongSuDungDichVu::dieuPhoiGiaTri($session, 'ngay_chup'), 0, 10);
                    if ($ngayChup === '' || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $ngayChup)) {
                        continue;
                    }
                    if ($ngayChup < $tuNgay || $ngayChup > $denNgay) {
                        continue;
                    }

                    $items[] = [
                        'id' => $hd->id,
                        'ngay_chup' => $ngayChup,
                        'gio_chup' => $this->normalizeGioChup(
                            HopDongSuDungDichVu::dieuPhoiGiaTri($session, 'gio_chup')
                        ),
                        'ma_hop_dong' => $hd->ma_hop_dong,
                        'ten_khach_hang' => $hd->ten_khach_hang,
                        'sdt_khach_hang' => $hd->sdt_khach_hang,
                        'loai_hop_dong_id' => $hd->loai_hop_dong_id,
                        'ten_hop_dong' => $hd->loaiHopDong?->ten_hop_dong,
                        'ma_loai_hop_dong' => $hd->loaiHopDong?->ma_hop_dong,
                        'trang_thai' => $hd->trang_thai,
                    ];
                }
            }

            usort($items, function (array $a, array $b) {
                $dateCmp = strcmp((string) $a['ngay_chup'], (string) $b['ngay_chup']);
                if ($dateCmp !== 0) {
                    return $dateCmp;
                }
                $gioA = $a['gio_chup'] ?: '99:99';
                $gioB = $b['gio_chup'] ?: '99:99';
                $gioCmp = strcmp((string) $gioA, (string) $gioB);
                if ($gioCmp !== 0) {
                    return $gioCmp;
                }

                return ($a['id'] <=> $b['id']);
            });

            return response()->json([
                'loai_hop_dong' => $loaiHopDongs,
                'items' => array_values($items),
            ]);

        }, 'lấy thống kê lịch chụp make');
    }

    /**
     * Danh sách hợp đồng lịch chụp-make theo ngày chụp (+ loại HĐ).
     * Ngày lấy từ thong_tin_dieu_phoi.danh_sach_buoi_chup.
     * Loại trừ trang_thai: moi_tao, nhap, da_huy.
     *
     * Query: ngay_chup (required), loai_hop_dong_id (optional), page, per_page
     */
    public function lichChupMakeChiTiet(Request $request): JsonResponse
    {
        return $this->handleApi(function () use ($request) {
            $validated = $request->validate([
                'ngay_chup' => ['required', 'date'],
                'loai_hop_dong_id' => ['sometimes', 'nullable', 'integer', 'exists:danh_muc_loai_hop_dong,id'],
                'page' => ['sometimes', 'integer', 'min:1'],
                'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            ]);

            $ngayChup = Carbon::parse($validated['ngay_chup'])->toDateString();
            $loaiHopDongId = $validated['loai_hop_dong_id'] ?? null;
            $perPage = $validated['per_page'] ?? 20;

            $query = HopDongSuDungDichVu::query()
                ->with([
                    'loaiHopDong:id,ten_hop_dong,ma_hop_dong',
                    'nguoiTao:id,name,phone',
                ])
                ->whereNotIn('trang_thai', ['moi_tao', 'nhap', 'da_huy'])
                ->when($loaiHopDongId, fn ($q) => $q->where('loai_hop_dong_id', $loaiHopDongId));

            $this->applyDieuPhoiDateEqualsFilter($query, 'ngay_chup', $ngayChup);
            $query->orderBy('id');

            return response()->json($query->paginate($perPage));

        }, 'lấy danh sách hợp đồng lịch chụp make');
    }

    private function normalizeGioChup(mixed $gioChup): ?string
    {
        if (! is_string($gioChup)) {
            return null;
        }
        $gioChup = trim($gioChup);
        if ($gioChup === '' || $gioChup === 'null') {
            return null;
        }

        return $gioChup;
    }

    /**
     * Công việc điều phối của user đang đăng nhập.
     * Tab tiền kỳ: trang_thai_dieu_phoi = tien_ky và id user nằm trong
     * danh_sach_buoi_chup[*].{tho_chup|tho_make|quay_phim}.gia_tri
     *
     * Query: page, per_page, ket_qua_trang_thai, keyword, loai_hop_dong_id,
     * ngay_chup, ngay_tra_file_le, ngay_tra_file_in, ngay_khach_hen_qua,
     * co_file_goc (tiền kỳ / hậu kỳ), co_file_le, co_file_in (hậu kỳ): 1 = đã có link, 0 = chưa có
     * Tab lọc theo thong_tin_dieu_phoi.trang_thai_dieu_phoi (fallback ket_qua_hop_dong.trang_thai).
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
                    'tien_ky',
                    'hau_ky',
                    'gui_in',
                    'hoan_tat_san_xuat',
                ])],
                'keyword' => ['sometimes', 'nullable', 'string', 'max:255'],
                'loai_hop_dong_id' => ['sometimes', 'nullable', 'integer', 'exists:danh_muc_loai_hop_dong,id'],
                'ngay_chup' => ['sometimes', 'nullable', 'date'],
                'ngay_tra_file_le' => ['sometimes', 'nullable', 'date'],
                'ngay_tra_file_in' => ['sometimes', 'nullable', 'date'],
                'ngay_khach_hen_qua' => ['sometimes', 'nullable', 'date'],
                'co_file_goc' => ['sometimes', 'nullable', 'boolean'],
                'co_file_le' => ['sometimes', 'nullable', 'boolean'],
                'co_file_in' => ['sometimes', 'nullable', 'boolean'],
            ]);
            $perPage = $validated['per_page'] ?? 24;
            $ketQuaTrangThai = $validated['ket_qua_trang_thai'] ?? 'tien_ky';

            $filteredQuery = $this->congViecCuaToiContractQuery();
            $this->applyCongViecDieuPhoiListFilters($filteredQuery, $validated);

            $query = (clone $filteredQuery)
                ->with(['loaiHopDong:id,ten_hop_dong,ma_hop_dong']);
            $this->applyStaffFilterForTab($query, $userId, $ketQuaTrangThai, $user);
            $this->applyKetQuaTrangThaiFilter($query, $ketQuaTrangThai);
            if (in_array($ketQuaTrangThai, ['tien_ky', 'hau_ky'], true)) {
                $this->applyKetQuaFilePresenceFilters($query, $validated, $ketQuaTrangThai);
            }
            $query->orderByDesc('id');

            $paginator = $query->paginate($perPage);
            $payload = $paginator->toArray();
            $payload['tab_counts'] = $this->congViecTabCounts($filteredQuery, $userId, $user);

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

            $current = $this->resolveTrangThaiDieuPhoi($hop_dong_su_dung_dich_vu, $ketQua);
            if ($current !== null && $current !== '' && $current !== HopDongSuDungDichVu::TRANG_THAI_DIEU_PHOI_CHO_NHAN) {
                abort(422, 'Công việc đã được nhận.');
            }

            $this->persistDieuPhoiWorkflowStatus($hop_dong_su_dung_dich_vu, $ketQua, 'dang_xu_ly');

            return response()->json(
                $hop_dong_su_dung_dich_vu->fresh()->load(['loaiHopDong:id,ten_hop_dong,ma_hop_dong'])
            );
        }, 'nhận công việc điều phối');
    }

    /**
     * Cập nhật một field trong ket_qua_hop_dong (vd. link_file_in, link_file_goc).
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

            $allowedKeys = array_keys(HopDongSuDungDichVu::defaultKetQuaHopDong());
            $validated = $request->validate([
                'key' => ['required', 'string', Rule::in($allowedKeys)],
                'gia_tri' => ['required', 'string', 'max:2048'],
            ]);

            $key = $validated['key'];
            if ($key === 'trang_thai') {
                abort(422, 'Không thể cập nhật trạng thái qua endpoint này.');
            }

            $hauKyFileKeys = ['link_file_le', 'link_file_in'];
            $timestampKeys = ['link_file_goc', 'link_file_le', 'link_file_in'];

            if ($key === 'link_file_goc') {
                $canEditGoc = $this->userIsAdminOrCoordinator($user)
                    || HopDongSuDungDichVu::userIsDieuPhoiStaff(
                        $hop_dong_su_dung_dich_vu->thong_tin_dieu_phoi,
                        $userId,
                        ['tho_chup', 'quay_phim'],
                    );
                if (! $canEditGoc) {
                    abort(403, 'Bạn không có quyền cập nhật file gốc.');
                }
            } elseif (in_array($key, $hauKyFileKeys, true)) {
                $status = HopDongSuDungDichVu::trangThaiDieuPhoi(
                    $hop_dong_su_dung_dich_vu->thong_tin_dieu_phoi,
                );
                if ($status !== HopDongSuDungDichVu::TRANG_THAI_DIEU_PHOI_HAU_KY) {
                    abort(422, 'Chỉ được cập nhật file lẻ / file in ở bước hậu kỳ.');
                }
                if (! $this->userCanCapNhatFileHauKy($user, $hop_dong_su_dung_dich_vu->thong_tin_dieu_phoi)) {
                    abort(403, 'Bạn không có quyền cập nhật file hậu kỳ.');
                }
            } elseif (! $assigned) {
                abort(403, 'Bạn không được gán vào công việc điều phối này.');
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

            $fieldUpdate = ['gia_tri' => $giaTri];
            if (in_array($key, $timestampKeys, true)) {
                $fieldUpdate['thoi_gian_up_file'] = now()->toDateTimeString();
            }

            $ketQua[$key] = array_merge(
                $defaults[$key],
                is_array($ketQua[$key] ?? null) ? $ketQua[$key] : [],
                $fieldUpdate,
            );

            $hop_dong_su_dung_dich_vu->update(['ket_qua_hop_dong' => $ketQua]);

            return response()->json(
                $hop_dong_su_dung_dich_vu->fresh()->load(['loaiHopDong:id,ten_hop_dong,ma_hop_dong'])
            );
        }, 'cập nhật kết quả hợp đồng');
    }

    /**
     * Cập nhật ngày dùng chung trong thong_tin_dieu_phoi.
     * Bước tiền kỳ và hậu kỳ: admin/coordinator được sửa ngày trả file lẻ,
     * ngày trả file in và ngày khách hẹn qua.
     */
    public function capNhatNgayDieuPhoi(Request $request, HopDongSuDungDichVu $hop_dong_su_dung_dich_vu): JsonResponse
    {
        return $this->handleApi(function () use ($request, $hop_dong_su_dung_dich_vu) {
            $user = $request->user();
            if (! $user) {
                abort(401, 'Unauthenticated.');
            }

            $validated = $request->validate([
                'key' => ['required', 'string', Rule::in([
                    'ngay_tra_file_le',
                    'ngay_tra_file_in',
                    'ngay_khach_hen_qua',
                ])],
                'gia_tri' => ['nullable', 'date'],
            ]);

            $key = $validated['key'];
            $status = HopDongSuDungDichVu::trangThaiDieuPhoi(
                $hop_dong_su_dung_dich_vu->thong_tin_dieu_phoi,
            );

            $allowedStatuses = [
                HopDongSuDungDichVu::TRANG_THAI_DIEU_PHOI_TIEN_KY,
                HopDongSuDungDichVu::TRANG_THAI_DIEU_PHOI_HAU_KY,
            ];
            if (! in_array($status, $allowedStatuses, true)) {
                abort(422, 'Chỉ được cập nhật ngày điều phối ở bước tiền kỳ hoặc hậu kỳ.');
            }
            if (! $this->userIsAdminOrCoordinator($user)) {
                abort(403, 'Chỉ admin hoặc coordinator được cập nhật ngày điều phối.');
            }

            $raw = $validated['gia_tri'] ?? null;
            $normalized = '';
            if ($raw !== null && $raw !== '') {
                $normalized = substr((string) $raw, 0, 10);
                if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $normalized)) {
                    abort(422, 'Ngày không hợp lệ.');
                }
            }

            $dieuPhoi = is_array($hop_dong_su_dung_dich_vu->thong_tin_dieu_phoi)
                ? $hop_dong_su_dung_dich_vu->thong_tin_dieu_phoi
                : [];
            $dieuPhoi[$key] = $normalized;
            $hop_dong_su_dung_dich_vu->update(['thong_tin_dieu_phoi' => $dieuPhoi]);

            return response()->json(
                $hop_dong_su_dung_dich_vu->fresh()->load(['loaiHopDong:id,ten_hop_dong,ma_hop_dong'])
            );
        }, 'cập nhật ngày điều phối');
    }

    /**
     * Chuyển công việc từ tiền kỳ sang hậu kỳ.
     * Yêu cầu thong_tin_dieu_phoi.trang_thai_dieu_phoi = tien_ky và đã có link_file_goc.
     */
    public function chuyenHauKy(Request $request, HopDongSuDungDichVu $hop_dong_su_dung_dich_vu): JsonResponse
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

            if (! $assigned && ! $this->userIsAdminOrCoordinator($user)) {
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

            $current = $this->resolveTrangThaiDieuPhoi($hop_dong_su_dung_dich_vu, $ketQua);
            if ($current !== HopDongSuDungDichVu::TRANG_THAI_DIEU_PHOI_TIEN_KY) {
                abort(422, 'Chỉ có thể chuyển hậu kỳ khi công việc đang ở bước Tiền kỳ.');
            }

            $linkGoc = trim((string) ($ketQua['link_file_goc']['gia_tri'] ?? ''));
            if ($linkGoc === '') {
                abort(422, 'Cần có File gốc trước khi chuyển sang hậu kỳ.');
            }

            $this->persistDieuPhoiWorkflowStatus($hop_dong_su_dung_dich_vu, $ketQua, 'hau_ky');

            return response()->json(
                $hop_dong_su_dung_dich_vu->fresh()->load(['loaiHopDong:id,ten_hop_dong,ma_hop_dong'])
            );
        }, 'chuyển công việc sang hậu kỳ');
    }

    /**
     * Chuyển công việc từ hậu kỳ sang gửi in.
     * Yêu cầu thong_tin_dieu_phoi.trang_thai_dieu_phoi = hau_ky và đã có file lẻ + file in.
     */
    public function chuyenGuiIn(Request $request, HopDongSuDungDichVu $hop_dong_su_dung_dich_vu): JsonResponse
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

            if (! $assigned && ! $this->userIsAdminOrCoordinator($user)) {
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

            $current = $this->resolveTrangThaiDieuPhoi($hop_dong_su_dung_dich_vu, $ketQua);
            if ($current !== HopDongSuDungDichVu::TRANG_THAI_DIEU_PHOI_HAU_KY) {
                abort(422, 'Chỉ có thể chuyển gửi in khi công việc đang ở bước Hậu kỳ.');
            }

            $linkLe = trim((string) ($ketQua['link_file_le']['gia_tri'] ?? ''));
            $linkIn = trim((string) ($ketQua['link_file_in']['gia_tri'] ?? ''));
            if ($linkLe === '' || $linkIn === '') {
                abort(422, 'Cần có đủ File lẻ và File in trước khi chuyển sang gửi in.');
            }

            $this->persistDieuPhoiWorkflowStatus(
                $hop_dong_su_dung_dich_vu,
                $ketQua,
                HopDongSuDungDichVu::TRANG_THAI_DIEU_PHOI_GUI_IN,
            );

            return response()->json(
                $hop_dong_su_dung_dich_vu->fresh()->load(['loaiHopDong:id,ten_hop_dong,ma_hop_dong'])
            );
        }, 'chuyển công việc sang gửi in');
    }

    /**
     * Chuyển công việc từ gửi in sang hoàn tất sản xuất.
     * Yêu cầu thong_tin_dieu_phoi.trang_thai_dieu_phoi = gui_in.
     */
    public function chuyenHoanTatSanXuat(Request $request, HopDongSuDungDichVu $hop_dong_su_dung_dich_vu): JsonResponse
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

            if (! $assigned && ! $this->userIsAdminOrCoordinator($user)) {
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

            $current = $this->resolveTrangThaiDieuPhoi($hop_dong_su_dung_dich_vu, $ketQua);
            if ($current !== HopDongSuDungDichVu::TRANG_THAI_DIEU_PHOI_GUI_IN) {
                abort(422, 'Chỉ có thể hoàn tất sản xuất khi công việc đang ở bước Gửi in.');
            }

            $this->persistDieuPhoiWorkflowStatus(
                $hop_dong_su_dung_dich_vu,
                $ketQua,
                HopDongSuDungDichVu::TRANG_THAI_DIEU_PHOI_HOAN_TAT_SAN_XUAT,
            );

            return response()->json(
                $hop_dong_su_dung_dich_vu->fresh()->load(['loaiHopDong:id,ten_hop_dong,ma_hop_dong'])
            );
        }, 'hoàn tất sản xuất công việc');
    }

    /**
     * Gửi khách kiểm tra → ket_qua_hop_dong.trang_thai = gui_khach_kiem_tra.
     * Yêu cầu đã có link_file_goc và link_file_in.
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

            $current = $this->resolveTrangThaiDieuPhoi($hop_dong_su_dung_dich_vu, $ketQua);
            if ($current !== 'dang_xu_ly') {
                abort(422, 'Chỉ có thể gửi khách kiểm tra khi công việc đang xử lý.');
            }

            $linkGoc = trim((string) ($ketQua['link_file_goc']['gia_tri'] ?? ''));
            $linkIn = trim((string) ($ketQua['link_file_in']['gia_tri'] ?? ''));
            if ($linkGoc === '' || $linkIn === '') {
                abort(422, 'Cần có đủ File gốc và File in trước khi gửi khách kiểm tra.');
            }

            $this->persistDieuPhoiWorkflowStatus($hop_dong_su_dung_dich_vu, $ketQua, 'gui_khach_kiem_tra');

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

            $current = $this->resolveTrangThaiDieuPhoi($hop_dong_su_dung_dich_vu, $ketQua);
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

            $this->persistDieuPhoiWorkflowStatus($hop_dong_su_dung_dich_vu, $ketQua, $nextStatus);

            return response()->json(
                $hop_dong_su_dung_dich_vu->fresh()->load(['loaiHopDong:id,ten_hop_dong,ma_hop_dong'])
            );
        }, 'xử lý phản hồi khách kiểm tra');
    }

    /**
     * Bàn giao sản phẩm → ket_qua_hop_dong.trang_thai = cho_nghiem_thu.
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

            $current = $this->resolveTrangThaiDieuPhoi($hop_dong_su_dung_dich_vu, $ketQua);
            if ($current !== 'san_xuat_in_an') {
                abort(422, 'Chỉ có thể bàn giao khi công việc đang ở bước Sản xuất & in ấn.');
            }

            $this->persistDieuPhoiWorkflowStatus($hop_dong_su_dung_dich_vu, $ketQua, 'cho_nghiem_thu');

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

            $current = $this->resolveTrangThaiDieuPhoi($hop_dong_su_dung_dich_vu, $ketQua);
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

            $this->persistDieuPhoiWorkflowStatus($hop_dong_su_dung_dich_vu, $ketQua, $nextStatus);

            return response()->json(
                $hop_dong_su_dung_dich_vu->fresh()->load(['loaiHopDong:id,ten_hop_dong,ma_hop_dong'])
            );
        }, 'xử lý nghiệm thu');
    }

    /**
     * Kiểm tra mã giảm giá: mã mặc định hoặc SĐT khách hàng của HĐ hoàn thành.
     * Số tiền giảm = % cơ sở tính, không vượt số tiền giảm tối đa.
     */
    public function kiemTraMaGiamGia(Request $request): JsonResponse
    {
        return $this->handleApi(function () use ($request) {
            $validated = $request->validate([
                'ma_giam_gia' => ['required', 'string', 'max:100'],
                'co_so_tinh' => ['sometimes', 'nullable', 'integer', 'min:0'],
            ]);

            $ma = trim((string) $validated['ma_giam_gia']);
            $coSoTinh = (int) ($validated['co_so_tinh'] ?? 0);
            $config = $this->maGiamGiaConfig();
            $macDinh = trim((string) ($config['ma_giam_gia_mac_dinh'] ?? ''));

            $loai = null;
            if ($macDinh !== '' && strcasecmp($ma, $macDinh) === 0) {
                $loai = 'mac_dinh';
            } elseif ($this->hopDongHoanThanhTheoSdt($ma)) {
                $loai = 'sdt_khach_hang';
            }

            if ($loai === null) {
                return response()->json([
                    'hop_le' => false,
                    'so_tien_giam' => 0,
                    'message' => 'Mã giảm giá không khớp',
                ]);
            }

            $soTienGiam = $this->tinhSoTienGiamGia(
                $coSoTinh,
                (int) ($config['phan_tram_giam_gia'] ?? 0),
                (int) ($config['so_tien_giam_toi_da'] ?? 0),
            );

            return response()->json([
                'hop_le' => true,
                'so_tien_giam' => $soTienGiam,
                'loai' => $loai,
            ]);
        }, 'kiểm tra mã giảm giá');
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
            $validated = $this->syncThongTinDieuPhoiOnSave($validated);
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
            $validated = $this->syncThongTinDieuPhoiOnSave($validated, $hop_dong_su_dung_dich_vu);
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

            $paidAt = now();
            $paidAtLabel = $paidAt->format('H:i:s d/m/Y');
            $hinhThucLabel = $validated['hinh_thuc_thanh_toan'] === 'chuyen_khoan'
                ? 'Chuyển khoản'
                : 'Tiền mặt';
            $noteLine = sprintf(
                '[TT lần %d · %s · %s] %s',
                $slot,
                $paidAtLabel,
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
                "thoi_gian_thanh_toan_lan_{$slot}" => $paidAt,
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
            $ketQuaStatus = $this->resolveTrangThaiDieuPhoi($hop_dong_su_dung_dich_vu, $ketQua);

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
                $this->persistDieuPhoiWorkflowStatus($hop_dong_su_dung_dich_vu, $ketQua, $nextStatus);

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
                $this->persistDieuPhoiWorkflowStatus($hop_dong_su_dung_dich_vu, $ketQua, 'san_xuat_in_an');

                return response()->json($this->loadDetail($hop_dong_su_dung_dich_vu->fresh()));
            }

            $ketQua['trang_thai'] = array_merge(
                $ketQua['trang_thai'],
                ['gia_tri' => 'hoan_thanh']
            );
            $this->persistDieuPhoiWorkflowStatus($hop_dong_su_dung_dich_vu, $ketQua, 'hoan_thanh');

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
            'thong_tin_dieu_phoi' => [
                'nullable',
                'array',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $sessions = HopDongSuDungDichVu::normalizeDieuPhoiSessions($value);
                    if (count($sessions) > 6) {
                        $fail('Tối đa 6 lịch quay chụp.');

                        return;
                    }
                    foreach ($sessions as $session) {
                        $ten = is_array($session) ? ($session['ten_lich']['gia_tri'] ?? null) : null;
                        if (is_string($ten) && mb_strlen($ten) > 30) {
                            $fail('Tên lịch quay chụp tối đa 30 ký tự.');

                            return;
                        }
                    }
                },
            ],
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
            'concepts.*.concept_id' => ['required', 'integer', 'exists:concept,id'],
            'concepts.*.ngay_su_dung' => ['nullable', 'date'],
            'trang_phucs' => [$isUpdate ? 'sometimes' : 'nullable', 'array'],
            'trang_phucs.*.trang_phuc_id' => ['required', 'integer', 'exists:trang_phuc,id'],
            'trang_phucs.*.ngay_su_dung' => ['nullable', 'date'],
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
                    'ngay_su_dung' => $item['ngay_su_dung'] ?? null,
                ])
                ->values()
                ->all();
        }
        if (array_key_exists('trang_phucs', $validated)) {
            $validated['trang_phucs'] = collect($validated['trang_phucs'] ?? [])
                ->map(fn ($item) => [
                    'trang_phuc_id' => (int) $item['trang_phuc_id'],
                    'ngay_su_dung' => $item['ngay_su_dung'] ?? null,
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
     * @param  array<int, array{concept_id: int, ngay_su_dung: ?string}>  $items
     */
    private function syncConcepts(HopDongSuDungDichVu $hopDong, array $items): void
    {
        $hopDong->concepts()->delete();

        foreach ($items as $item) {
            HopDongDongSddvConcept::create([
                'ma_hop_dong_id' => $hopDong->id,
                'concept_id' => $item['concept_id'],
                'ngay_su_dung' => $item['ngay_su_dung'] ?: null,
            ]);
        }
    }

    /**
     * @param  array<int, array{trang_phuc_id: int, ngay_su_dung: ?string, ngay_bat_dau: ?string, ngay_ket_thuc: ?string}>  $items
     */
    private function syncTrangPhucs(HopDongSuDungDichVu $hopDong, array $items): void
    {
        $hopDong->trangPhucs()->delete();

        foreach ($items as $item) {
            HopDongDongSddvTrangPhuc::create([
                'ma_hop_dong_id' => $hopDong->id,
                'trang_phuc_id' => $item['trang_phuc_id'],
                'ngay_su_dung' => $item['ngay_su_dung'] ?: null,
                'ngay_bat_dau' => $item['ngay_bat_dau'] ?: null,
                'ngay_ket_thuc' => $item['ngay_ket_thuc'] ?: null,
            ]);
        }
    }

    /**
     * Hợp đồng đủ điều kiện vào công việc của tôi (loại nháp).
     */
    private function congViecCuaToiContractQuery()
    {
        return HopDongSuDungDichVu::query()
            ->whereNotIn('trang_thai', ['moi_tao', 'nhap']);
    }

    /**
     * Query gốc: hợp đồng gán user vào các field nhân sự điều phối (mọi buổi chụp).
     */
    private function congViecCuaToiBaseQuery(int $userId, ?array $staffKeys = null)
    {
        $query = $this->congViecCuaToiContractQuery();
        $this->applyDieuPhoiStaffAssignedFilter($query, $userId, $staffKeys);

        return $query;
    }

    /**
     * Tiền kỳ: chỉ thợ chụp / thợ make / quay phim.
     *
     * @return list<string>
     */
    private function staffKeysForCongViecTab(string $tab): array
    {
        if ($tab === 'tien_ky') {
            return HopDongSuDungDichVu::DIEU_PHOI_TIEN_KY_STAFF_KEYS;
        }

        if ($tab === 'hau_ky') {
            return HopDongSuDungDichVu::DIEU_PHOI_HAU_KY_STAFF_KEYS;
        }

        return HopDongSuDungDichVu::DIEU_PHOI_STAFF_KEYS;
    }

    private function userIsAdminOrCoordinator($user): bool
    {
        if (! $user) {
            return false;
        }

        $role = strtolower(trim((string) ($user->role ?? '')));
        if (in_array($role, ['admin', 'coordinator', 'coordination'], true)) {
            return true;
        }

        $ten = $this->userVaiTroTen($user);
        if ($ten === '') {
            return false;
        }

        return str_contains($ten, 'coordinator')
            || str_contains($ten, 'coordination')
            || str_contains($ten, 'điều phối')
            || str_contains($ten, 'dieu phoi');
    }

    private function userVaiTroTen($user): string
    {
        if (! $user) {
            return '';
        }

        $user->loadMissing('nhanVien.vaiTro');

        return mb_strtolower(trim((string) ($user->nhanVien?->vaiTro?->ten_vai_tro ?? '')), 'UTF-8');
    }

    /** Tài khoản thợ edit / thợ dựng video / thợ hậu kỳ. */
    private function userHasHauKyJobRole($user): bool
    {
        $ten = $this->userVaiTroTen($user);
        if ($ten === '') {
            return false;
        }

        return str_contains($ten, 'thợ edit')
            || str_contains($ten, 'tho edit')
            || str_contains($ten, 'dựng video')
            || str_contains($ten, 'dung video')
            || str_contains($ten, 'thợ dựng')
            || str_contains($ten, 'hậu kỳ')
            || str_contains($ten, 'hau ky')
            || (bool) preg_match('/\beditor\b/u', $ten);
    }

    private function userCanCapNhatFileHauKy($user, mixed $thongTinDieuPhoi): bool
    {
        if ($this->userIsAdminOrCoordinator($user) || $this->userHasHauKyJobRole($user)) {
            return true;
        }

        $userId = (int) ($user->id ?? 0);
        if ($userId <= 0) {
            return false;
        }

        return HopDongSuDungDichVu::userIsDieuPhoiStaff(
            $thongTinDieuPhoi,
            $userId,
            HopDongSuDungDichVu::DIEU_PHOI_HAU_KY_STAFF_KEYS,
        );
    }

    /**
     * Tiền kỳ / hậu kỳ: admin/coordinator xem mọi HĐ ở bước này; nhân sự khác lọc theo thợ.
     */
    private function applyStaffFilterForTab($query, int $userId, string $tab, $user): void
    {
        if (in_array($tab, ['tien_ky', 'hau_ky', 'gui_in'], true) && $this->userIsAdminOrCoordinator($user)) {
            return;
        }

        $this->applyDieuPhoiStaffAssignedFilter(
            $query,
            $userId,
            $this->staffKeysForCongViecTab($tab),
        );
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

        $dateFields = ['ngay_chup', 'ngay_tra_file_le', 'ngay_tra_file_in', 'ngay_khach_hen_qua'];
        foreach ($dateFields as $field) {
            $value = $filters[$field] ?? null;
            if ($value === null || $value === '') {
                continue;
            }

            $this->applyDieuPhoiDateEqualsFilter($query, $field, (string) $value);
        }
    }

    private const DIEU_PHOI_SESSION_INDEX_MAX = 6;

    /**
     * JSON path từng buổi: $.danh_sach_buoi_chup[i].{relativePath}
     *
     * @return list<string>
     */
    private function dieuPhoiSessionJsonPaths(string $relativePath): array
    {
        $paths = [];
        for ($i = 0; $i < self::DIEU_PHOI_SESSION_INDEX_MAX; $i++) {
            $paths[] = '$.danh_sach_buoi_chup['.$i.'].'.$relativePath;
        }

        return $paths;
    }

    /**
     * Lọc hợp đồng có user nằm trong field nhân sự của buổi chụp.
     * Path dùng [i] vì danh_sach_buoi_chup là mảng; arrow ->0-> sẽ thành object key "0".
     *
     * @param  list<string>|null  $staffKeys
     */
    private function applyDieuPhoiStaffAssignedFilter($query, int $userId, ?array $staffKeys = null): void
    {
        $staffKeys = $staffKeys ?: HopDongSuDungDichVu::DIEU_PHOI_STAFF_KEYS;
        $query->where(function ($q) use ($staffKeys, $userId) {
            foreach ($staffKeys as $key) {
                foreach ($this->dieuPhoiSessionJsonPaths($key.'.gia_tri') as $jsonPath) {
                    $this->orJsonContainsUserAtExtractPath($q, $jsonPath, $userId);
                }
            }
        });
    }

    private function orJsonContainsUserAtExtractPath($query, string $jsonPath, int $userId): void
    {
        // MariaDB không hỗ trợ CAST(? AS JSON); truyền candidate đã json_encode.
        $query->orWhere(function ($inner) use ($jsonPath, $userId) {
            $inner->whereRaw(
                "JSON_CONTAINS(thong_tin_dieu_phoi, ?, '{$jsonPath}')",
                [json_encode($userId)]
            )->orWhereRaw(
                "JSON_CONTAINS(thong_tin_dieu_phoi, ?, '{$jsonPath}')",
                [json_encode((string) $userId)]
            );
        });
    }

    /**
     * Lọc nâng cao theo thong_tin_dieu_phoi.
     *
     * @param  array<string, mixed>  $filters
     */
    private function applyDieuPhoiAdvancedFilters($query, array $filters): void
    {
        $loaiQuayChupId = $filters['loai_quay_chup_id'] ?? null;
        if ($loaiQuayChupId) {
            $this->applyDieuPhoiJsonValueEquals($query, 'loai_quay_chup.gia_tri.id', (int) $loaiQuayChupId);
        }

        $ngayChupTu = $filters['ngay_chup_tu'] ?? null;
        $ngayChupDen = $filters['ngay_chup_den'] ?? null;
        if ($ngayChupTu || $ngayChupDen) {
            $this->applyDieuPhoiDateRangeFilter(
                $query,
                'ngay_chup',
                $ngayChupTu ? (string) $ngayChupTu : null,
                $ngayChupDen ? (string) $ngayChupDen : null,
            );
        }

        $soDiemChup = $filters['so_diem_chup'] ?? null;
        if ($soDiemChup !== null && $soDiemChup !== '') {
            $this->applyDieuPhoiJsonValueEquals($query, 'so_diem_chup.gia_tri', (int) $soDiemChup);
        }

        $staffMap = [
            'co_tho_chup' => 'tho_chup',
            'co_tho_make' => 'tho_make',
            'co_quay_phim' => 'quay_phim',
            'co_tho_edit' => 'tho_edit',
        ];
        foreach ($staffMap as $param => $field) {
            if (! array_key_exists($param, $filters) || $filters[$param] === null || $filters[$param] === '') {
                continue;
            }
            $this->applyDieuPhoiStaffPresenceFilter(
                $query,
                $field,
                in_array($filters[$param], [1, '1', true], true),
            );
        }
    }

    /**
     * So khớp giá trị JSON trong bất kỳ buổi điều phối nào.
     */
    private function applyDieuPhoiJsonValueEquals($query, string $relativePath, int|string $value): void
    {
        if (! preg_match('/^[a-z0-9_.]+$/', $relativePath)) {
            return;
        }
        $query->where(function ($q) use ($relativePath, $value) {
            foreach ($this->dieuPhoiSessionJsonPaths($relativePath) as $jsonPath) {
                $q->orWhereRaw(
                    "JSON_UNQUOTE(JSON_EXTRACT(thong_tin_dieu_phoi, '{$jsonPath}')) = ?",
                    [(string) $value]
                );
            }
        });
    }

    /**
     * Lọc khoảng ngày điều phối.
     */
    private function applyDieuPhoiDateRangeFilter($query, string $field, ?string $from, ?string $to): void
    {
        $allowed = ['ngay_chup', 'ngay_tra_file_le', 'ngay_tra_file_in', 'ngay_khach_hen_qua'];
        if (! in_array($field, $allowed, true)) {
            return;
        }
        $from = $from ? substr($from, 0, 10) : null;
        $to = $to ? substr($to, 0, 10) : null;
        if (! $from && ! $to) {
            return;
        }

        $query->where(function ($q) use ($field, $from, $to) {
            foreach ($this->dieuPhoiDateJsonPaths($field) as $jsonPath) {
                $this->orDieuPhoiDateInRange($q, $jsonPath, $from, $to);
            }
        });
    }

    private function orDieuPhoiDateInRange($query, string $jsonPath, ?string $from, ?string $to): void
    {
        $expr = "LEFT(JSON_UNQUOTE(JSON_EXTRACT(thong_tin_dieu_phoi, '{$jsonPath}')), 10)";
        $query->orWhere(function ($inner) use ($expr, $from, $to) {
            $inner->whereRaw("{$expr} REGEXP '^[0-9]{4}-[0-9]{2}-[0-9]{2}$'");
            if ($from) {
                $inner->whereRaw("{$expr} >= ?", [$from]);
            }
            if ($to) {
                $inner->whereRaw("{$expr} <= ?", [$to]);
            }
        });
    }

    /**
     * Buổi đã có nhân sự khi tho_* (mảng user id) hoặc tho_*_ngoai (chuỗi) có gia_tri.
     * Null / rỗng / [] = chưa có.
     * IFNULL: buổi không tồn tại trả JSON NULL; NULL > 0 làm cả cụm OR thành NULL,
     * khiến whereNot loại luôn hợp đồng chưa gán.
     */
    private function dieuPhoiSessionHasStaffSql(int $index, string $key): string
    {
        $staffPath = '$.danh_sach_buoi_chup['.$index.'].'.$key.'.gia_tri';
        $ngoaiPath = '$.danh_sach_buoi_chup['.$index.'].'.$key.'_ngoai.gia_tri';
        $hasInternal = "IFNULL(JSON_LENGTH(JSON_EXTRACT(thong_tin_dieu_phoi, '{$staffPath}')), 0) > 0";
        $ngoaiText = "TRIM(IFNULL(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(thong_tin_dieu_phoi, '{$ngoaiPath}')), 'null'), ''))";
        $hasNgoai = "CHAR_LENGTH({$ngoaiText}) > 0";

        return "({$hasInternal} OR {$hasNgoai})";
    }

    /**
     * Lọc hợp đồng đã gán / chưa gán nhân sự điều phối (tho_chup, tho_make, quay_phim, tho_edit).
     * Duyệt từng buổi trong thong_tin_dieu_phoi.danh_sach_buoi_chup.
     *
     * Có thợ make: chỉ cần một buổi có tho_make hoặc tho_make_ngoai (không rỗng).
     * Chưa có thợ make: không buổi nào có cả hai.
     *
     * gia_tri null hoặc rỗng = chưa có; chỉ cần 1 trong 2 thì buổi đó là đã có.
     * Cùng quy tắc với thợ chụp / quay phim / thợ edit (tho_* hoặc tho_*_ngoai).
     */
    private function applyDieuPhoiStaffPresenceFilter($query, string $key, bool $hasStaff): void
    {
        $allowed = ['tho_chup', 'tho_make', 'tho_edit', 'quay_phim', 'tho_dung_video'];
        if (! in_array($key, $allowed, true)) {
            return;
        }

        $assigned = function ($q) use ($key) {
            for ($i = 0; $i < self::DIEU_PHOI_SESSION_INDEX_MAX; $i++) {
                $q->orWhereRaw($this->dieuPhoiSessionHasStaffSql($i, $key));
            }
        };

        if ($hasStaff) {
            // Có thợ make: chỉ cần một buổi có tho_make hoặc tho_make_ngoai (không rỗng)
            $query->where($assigned);

            return;
        }

        // Chưa có thợ make: không buổi nào có cả hai
        $query->whereNot($assigned);
    }

    /**
     * @return list<string>
     */
    private function dieuPhoiDateJsonPaths(string $field): array
    {
        if (in_array($field, ['ngay_tra_file_le', 'ngay_tra_file_in', 'ngay_khach_hen_qua'], true)) {
            return ["$.{$field}"];
        }

        return $this->dieuPhoiSessionJsonPaths("{$field}.gia_tri");
    }

    /**
     * Lọc theo ngày điều phối.
     */
    private function applyDieuPhoiDateEqualsFilter($query, string $field, string $date): void
    {
        $date = substr($date, 0, 10);
        $allowed = ['ngay_chup', 'ngay_tra_file_le', 'ngay_tra_file_in', 'ngay_khach_hen_qua'];
        if (! in_array($field, $allowed, true)) {
            return;
        }
        $query->where(function ($q) use ($field, $date) {
            foreach ($this->dieuPhoiDateJsonPaths($field) as $jsonPath) {
                $q->orWhereRaw(
                    "LEFT(JSON_UNQUOTE(JSON_EXTRACT(thong_tin_dieu_phoi, '{$jsonPath}')), 10) = ?",
                    [$date]
                );
            }
        });
    }

    /**
     * Lọc theo đã có / chưa có link file trong ket_qua_hop_dong.
     * Tiền kỳ: file gốc. Hậu kỳ: file gốc, file lẻ, file in.
     *
     * @param  array<string, mixed>  $filters
     */
    private function applyKetQuaFilePresenceFilters($query, array $filters, string $tab): void
    {
        $map = match ($tab) {
            'tien_ky' => [
                'co_file_goc' => 'link_file_goc',
            ],
            'hau_ky' => [
                'co_file_goc' => 'link_file_goc',
                'co_file_le' => 'link_file_le',
                'co_file_in' => 'link_file_in',
            ],
            default => [],
        };

        foreach ($map as $param => $jsonKey) {
            if (! array_key_exists($param, $filters) || $filters[$param] === null || $filters[$param] === '') {
                continue;
            }
            $hasLink = $this->parseOptionalBool($filters[$param]);
            if ($hasLink === null) {
                continue;
            }
            $this->applyKetQuaLinkPresenceFilter($query, $jsonKey, $hasLink);
        }
    }

    /**
     * Link ket_qua_hop_dong.{key}.gia_tri: null / "null" / chuỗi trắng = chưa có.
     */
    private function applyKetQuaLinkPresenceFilter($query, string $key, bool $hasLink): void
    {
        $allowed = ['link_file_goc', 'link_file_le', 'link_file_in'];
        if (! in_array($key, $allowed, true)) {
            return;
        }

        $expr = "TRIM(IFNULL(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(ket_qua_hop_dong, '$.{$key}.gia_tri')), 'null'), ''))";
        if ($hasLink) {
            $query->whereRaw("CHAR_LENGTH({$expr}) > 0");
        } else {
            $query->whereRaw("CHAR_LENGTH({$expr}) = 0");
        }
    }

    private function parseOptionalBool(mixed $value): ?bool
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (in_array($value, [1, '1', true, 'true'], true)) {
            return true;
        }
        if (in_array($value, [0, '0', false, 'false'], true)) {
            return false;
        }

        return null;
    }

    /**
     * Lọc tab công việc theo thong_tin_dieu_phoi.trang_thai_dieu_phoi,
     * fallback ket_qua_hop_dong.trang_thai.gia_tri.
     */
    private function applyKetQuaTrangThaiFilter($query, string $ketQuaTrangThai): void
    {
        $dieuPhoiExpr = "NULLIF(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(thong_tin_dieu_phoi, '$.trang_thai_dieu_phoi')), 'null'), '')";
        $ketQuaExpr = "NULLIF(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(ket_qua_hop_dong, '$.trang_thai.gia_tri')), 'null'), '')";
        $effective = "COALESCE({$dieuPhoiExpr}, {$ketQuaExpr})";

        $query->whereRaw("{$effective} = ?", [$ketQuaTrangThai]);
    }

    /**
     * Ưu tiên thong_tin_dieu_phoi.trang_thai_dieu_phoi, fallback ket_qua_hop_dong.
     *
     * @param  array<string, mixed>|null  $ketQua
     */
    private function resolveTrangThaiDieuPhoi(HopDongSuDungDichVu $hopDong, ?array $ketQua = null): ?string
    {
        $fromDieuPhoi = HopDongSuDungDichVu::trangThaiDieuPhoi($hopDong->thong_tin_dieu_phoi);
        if ($fromDieuPhoi !== null) {
            return $fromDieuPhoi;
        }

        $ketQua ??= is_array($hopDong->ket_qua_hop_dong) ? $hopDong->ket_qua_hop_dong : [];
        $fromKetQua = $ketQua['trang_thai']['gia_tri'] ?? null;
        if ($fromKetQua === null || $fromKetQua === '') {
            return null;
        }

        return (string) $fromKetQua;
    }

    /**
     * Đồng bộ trạng thái workflow: thong_tin_dieu_phoi.trang_thai_dieu_phoi + ket_qua_hop_dong.trang_thai.
     *
     * @param  array<string, mixed>  $ketQua
     */
    private function persistDieuPhoiWorkflowStatus(
        HopDongSuDungDichVu $hopDong,
        array $ketQua,
        string $status,
        array $extra = [],
    ): void {
        $defaults = HopDongSuDungDichVu::defaultKetQuaHopDong();
        $ketQua['trang_thai'] = array_merge(
            $defaults['trang_thai'],
            is_array($ketQua['trang_thai'] ?? null) ? $ketQua['trang_thai'] : [],
            ['gia_tri' => $status]
        );

        $dieuPhoi = is_array($hopDong->thong_tin_dieu_phoi) ? $hopDong->thong_tin_dieu_phoi : [];
        $dieuPhoi[HopDongSuDungDichVu::TRANG_THAI_DIEU_PHOI_KEY] = $status;

        $hopDong->update(array_merge($extra, [
            'ket_qua_hop_dong' => $ketQua,
            'thong_tin_dieu_phoi' => $dieuPhoi,
        ]));
    }

    /**
     * Khi lưu thong_tin_dieu_phoi có chọn/nhập thợ → trang_thai_dieu_phoi = tien_ky.
     *
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function syncThongTinDieuPhoiOnSave(array $validated, ?HopDongSuDungDichVu $existing = null): array
    {
        if (! array_key_exists('thong_tin_dieu_phoi', $validated) || ! is_array($validated['thong_tin_dieu_phoi'])) {
            return $validated;
        }

        $existingStatus = HopDongSuDungDichVu::trangThaiDieuPhoi($existing?->thong_tin_dieu_phoi)
            ?? $this->resolveTrangThaiDieuPhoiFallback($existing);
        $payload = HopDongSuDungDichVu::withTienKyIfStaffAssigned(
            $validated['thong_tin_dieu_phoi'],
            $existingStatus,
        );
        $validated['thong_tin_dieu_phoi'] = $payload;

        if (($payload[HopDongSuDungDichVu::TRANG_THAI_DIEU_PHOI_KEY] ?? null) !== HopDongSuDungDichVu::TRANG_THAI_DIEU_PHOI_TIEN_KY) {
            return $validated;
        }

        $ketQua = $this->normalizeKetQuaHopDong(
            $validated['ket_qua_hop_dong'] ?? $existing?->ket_qua_hop_dong
        );
        $currentKetQua = $ketQua['trang_thai']['gia_tri'] ?? null;
        $lockedKetQua = [
            HopDongSuDungDichVu::TRANG_THAI_DIEU_PHOI_CHO_NHAN,
            HopDongSuDungDichVu::TRANG_THAI_DIEU_PHOI_TIEN_KY,
        ];
        if ($currentKetQua !== null && $currentKetQua !== '' && ! in_array($currentKetQua, $lockedKetQua, true)) {
            return $validated;
        }

        $ketQua['trang_thai']['gia_tri'] = HopDongSuDungDichVu::TRANG_THAI_DIEU_PHOI_TIEN_KY;
        $validated['ket_qua_hop_dong'] = $ketQua;

        return $validated;
    }

    private function resolveTrangThaiDieuPhoiFallback(?HopDongSuDungDichVu $existing): ?string
    {
        if (! $existing) {
            return null;
        }

        $ketQua = is_array($existing->ket_qua_hop_dong) ? $existing->ket_qua_hop_dong : [];
        $value = $ketQua['trang_thai']['gia_tri'] ?? null;
        if ($value === null || $value === '') {
            return null;
        }

        return (string) $value;
    }

    /**
     * @return array<string, int>
     */
    private function congViecTabCounts($filteredQuery, int $userId, $user): array
    {
        $statuses = [
            'tien_ky',
            'hau_ky',
            'gui_in',
            'hoan_tat_san_xuat',
        ];

        $counts = [];
        foreach ($statuses as $status) {
            $q = clone $filteredQuery;
            $this->applyStaffFilterForTab($q, $userId, $status, $user);
            $this->applyKetQuaTrangThaiFilter($q, $status);
            $counts[$status] = (int) $q->count();
        }

        return $counts;
    }

    /**
     * @return array{phan_tram_giam_gia?: mixed, so_tien_giam_toi_da?: mixed, ma_giam_gia_mac_dinh?: mixed}
     */
    private function maGiamGiaConfig(): array
    {
        $row = CauHinhJson::query()->first();
        $all = is_array($row?->thong_tin_cau_hinh) ? $row->thong_tin_cau_hinh : [];

        return is_array($all['ma_giam_gia'] ?? null) ? $all['ma_giam_gia'] : [];
    }

    private function hopDongHoanThanhTheoSdt(string $ma): bool
    {
        $digits = preg_replace('/\D+/', '', $ma) ?? '';
        if ($digits === '') {
            return false;
        }

        $normalizedExpr = "REPLACE(REPLACE(REPLACE(REPLACE(IFNULL(sdt_khach_hang, ''), ' ', ''), '.', ''), '-', ''), '+', '')";
        $last9 = strlen($digits) >= 9 ? substr($digits, -9) : $digits;

        return HopDongSuDungDichVu::query()
            ->where('trang_thai', 'hoan_thanh')
            ->whereNotNull('sdt_khach_hang')
            ->where('sdt_khach_hang', '!=', '')
            ->where(function ($q) use ($normalizedExpr, $digits, $last9) {
                $q->whereRaw("{$normalizedExpr} = ?", [$digits]);
                if (strlen($last9) >= 9) {
                    $q->orWhereRaw("RIGHT({$normalizedExpr}, 9) = ?", [$last9]);
                }
            })
            ->exists();
    }

    private function tinhSoTienGiamGia(int $coSoTinh, int $phanTram, int $soTienToiDa): int
    {
        $coSoTinh = max(0, $coSoTinh);
        $phanTram = max(0, min(100, $phanTram));
        $theoPhanTram = (int) round($coSoTinh * $phanTram / 100);
        $soTienGiam = $soTienToiDa > 0 ? min($theoPhanTram, $soTienToiDa) : $theoPhanTram;

        return max(0, min($soTienGiam, $coSoTinh));
    }
}
