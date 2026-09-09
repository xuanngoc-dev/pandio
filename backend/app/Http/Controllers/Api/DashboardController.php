<?php

namespace App\Http\Controllers\Api;

use App\Models\DanhMucNguonKhach;
use App\Models\HopDongChoThueTrangPhuc;
use App\Models\HopDongChoThueTrangPhucSanPhamChoThue;
use App\Models\HopDongSuDungDichVu;
use App\Models\KhachHangNoteKhachMoi;
use App\Models\NhanVien;
use App\Models\PhieuThuChi;
use App\Models\PhongBan;
use App\Models\ReportQuangCao;
use App\Models\TrangPhuc;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API thống kê Tổng quan (dashboard) theo từng tab.
 */
class DashboardController extends BaseApiController
{
    private const TIMEZONE = 'Asia/Ho_Chi_Minh';

    /** Trạng thái HĐ chưa ký / nháp — loại khỏi số HĐ ký & DT cho thuê theo kỳ. */
    private const HD_DRAFT_STATUSES = ['moi_tao', 'nhap'];

    private const HD_EXCLUDED_STATUSES = ['moi_tao', 'nhap', 'da_huy'];

    /**
     * KPI cards tab CEO & Admin theo khoảng ngày.
     *
     * Query: tu_ngay, den_ngay (YYYY-MM-DD; mặc định tháng hiện tại)
     * Tương thích cũ: thang (YYYY-MM) nếu không truyền khoảng ngày
     */
    public function ceoAdmin(Request $request): JsonResponse
    {
        return $this->handleApi(function () use ($request) {
            $validated = $request->validate([
                'tu_ngay' => ['sometimes', 'nullable', 'date'],
                'den_ngay' => ['sometimes', 'nullable', 'date', 'after_or_equal:tu_ngay'],
                'thang' => ['sometimes', 'nullable', 'string', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
            ]);

            [$start, $end] = $this->resolvePeriodBounds($validated);

            $doanhThuSddv = $this->doanhThuSddvTrongKy($start, $end);
            $doanhThuChoThue = $this->doanhThuChoThueTrongKy($start, $end);
            $tongDoanhThu = $doanhThuSddv + $doanhThuChoThue;

            $loiNhuan = $this->loiNhuanTruocThue($start, $end);
            $hopDong = $this->hopDongKyTrongKy($start, $end);
            $khachHang = $this->tongKhachHang();
            $noteKhachMoi = $this->noteKhachMoiTrongKy($start, $end, $hopDong['so_hop_dong_sddv_ky']);
            $quangCao = $this->quangCaoTrongKy($start, $end);
            $nhanSu = $this->tongNhanSu();
            $quyLuong = $this->quyLuongTrongKy($start, $end);
            $coCauDoanhThu = $this->coCauDoanhThuTheoCreatedAt($start, $end);
            $bieuDo12Thang = $this->bieuDoDoanhThu12Thang();

            return response()->json([
                'tu_ngay' => $start->toDateString(),
                'den_ngay' => $end->toDateString(),
                'tong_doanh_thu' => $tongDoanhThu,
                'doanh_thu_sddv' => $doanhThuSddv,
                'doanh_thu_cho_thue' => $doanhThuChoThue,
                'loi_nhuan_truoc_thue' => $loiNhuan['loi_nhuan_truoc_thue'],
                'tong_thu_da_duyet' => $loiNhuan['tong_thu_da_duyet'],
                'tong_chi_da_duyet' => $loiNhuan['tong_chi_da_duyet'],
                'so_hop_dong_ky' => $hopDong['so_hop_dong_ky'],
                'so_hop_dong_sddv_ky' => $hopDong['so_hop_dong_sddv_ky'],
                'so_hop_dong_cho_thue_ky' => $hopDong['so_hop_dong_cho_thue_ky'],
                'tong_khach_hang' => $khachHang['tong_khach_hang'],
                'khach_hang' => $khachHang,
                'tong_note_khach_moi' => $noteKhachMoi['tong_note_khach_moi'],
                'so_note_da_den' => $noteKhachMoi['so_note_da_den'],
                'ty_le_chot' => $noteKhachMoi['ty_le_chot'],
                'tong_cp_quang_cao' => $quangCao['tong_cp_quang_cao'],
                'tong_lead_quang_cao' => $quangCao['tong_lead_quang_cao'],
                'cpl_trung_binh' => $quangCao['cpl_trung_binh'],
                'tong_nhan_su' => $nhanSu['tong_nhan_su'],
                'nhan_su_active' => $nhanSu['nhan_su_active'],
                'quy_luong' => $quyLuong['quy_luong'],
                'quy_luong_meta' => [
                    'so_nhan_vien' => $quyLuong['so_nhan_vien'],
                    'da_chot' => $quyLuong['da_chot'],
                    'nguon' => $quyLuong['nguon'],
                    'so_thang' => $quyLuong['so_thang'],
                ],
                'co_cau_doanh_thu' => $coCauDoanhThu,
                'bieu_do_12_thang' => $bieuDo12Thang,
            ]);
        }, 'lấy thống kê CEO & Admin');
    }

    /**
     * KPI + biểu đồ tab Kinh doanh theo khoảng ngày.
     *
     * Query: tu_ngay, den_ngay (YYYY-MM-DD; mặc định tháng hiện tại)
     * Tương thích cũ: thang (YYYY-MM) nếu không truyền khoảng ngày
     */
    public function kinhDoanh(Request $request): JsonResponse
    {
        return $this->handleApi(function () use ($request) {
            $validated = $request->validate([
                'tu_ngay' => ['sometimes', 'nullable', 'date'],
                'den_ngay' => ['sometimes', 'nullable', 'date', 'after_or_equal:tu_ngay'],
                'thang' => ['sometimes', 'nullable', 'string', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
            ]);

            [$start, $end] = $this->resolvePeriodBounds($validated);

            $doanhThuSddv = $this->doanhThuSddvTrongKy($start, $end);
            $doanhThuTp = $this->doanhThuChoThueTrongKy($start, $end);
            $hopDong = $this->hopDongKyTrongKy($start, $end);
            $noteKhachMoi = $this->noteKhachMoiTrongKy($start, $end, $hopDong['so_hop_dong_sddv_ky']);
            $tyLeDen = $this->tyLeDenTrongKy($start, $end, $noteKhachMoi['tong_note_khach_moi']);
            $trangThaiNote = $this->bieuDoTrangThaiNoteTrongKy($start, $end);
            $nguonKhach = $this->bieuDoNguonKhachTrongKy($start, $end);
            $xepHang = $this->xepHangSaleTrongKy($start, $end);

            return response()->json([
                'tu_ngay' => $start->toDateString(),
                'den_ngay' => $end->toDateString(),
                'doanh_thu_sddv' => $doanhThuSddv,
                'doanh_thu_tp' => $doanhThuTp,
                'so_hop_dong' => $hopDong['so_hop_dong_ky'],
                'so_hop_dong_sddv_ky' => $hopDong['so_hop_dong_sddv_ky'],
                'so_hop_dong_cho_thue_ky' => $hopDong['so_hop_dong_cho_thue_ky'],
                'ty_le_chot' => $noteKhachMoi['ty_le_chot'],
                'so_note_da_den' => $noteKhachMoi['so_note_da_den'],
                'tong_note_khach_moi' => $noteKhachMoi['tong_note_khach_moi'],
                'ty_le_den' => $tyLeDen['ty_le_den'],
                'so_note_den_theo_tao' => $tyLeDen['so_note_den_theo_tao'],
                'bieu_do_trang_thai_note' => $trangThaiNote,
                'bieu_do_nguon_khach' => $nguonKhach,
                'top_sale_so_hd' => $xepHang['top_sale_so_hd'],
                'top_sale_doanh_thu' => $xepHang['top_sale_doanh_thu'],
            ]);
        }, 'lấy thống kê Kinh doanh');
    }

    /**
     * KPI cards tab Marketing theo khoảng ngày (nguồn: report_quang_cao).
     *
     * Query: tu_ngay, den_ngay (YYYY-MM-DD; mặc định tháng hiện tại)
     * Tương thích cũ: thang (YYYY-MM) nếu không truyền khoảng ngày
     */
    public function marketing(Request $request): JsonResponse
    {
        return $this->handleApi(function () use ($request) {
            $validated = $request->validate([
                'tu_ngay' => ['sometimes', 'nullable', 'date'],
                'den_ngay' => ['sometimes', 'nullable', 'date', 'after_or_equal:tu_ngay'],
                'thang' => ['sometimes', 'nullable', 'string', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
            ]);

            [$start, $end] = $this->resolvePeriodBounds($validated);
            $stats = $this->marketingQuangCaoTrongKy($start, $end);
            $bieuDoTheoNgay = $this->marketingBieuDoTheoNgay($start, $end);

            return response()->json(array_merge([
                'tu_ngay' => $start->toDateString(),
                'den_ngay' => $end->toDateString(),
                'bieu_do_theo_ngay' => $bieuDoTheoNgay,
            ], $stats));
        }, 'lấy thống kê Marketing');
    }

    /**
     * KPI cards tab Sản xuất & điều phối theo khoảng ngày.
     *
     * Query: tu_ngay, den_ngay (YYYY-MM-DD; mặc định tháng hiện tại)
     * Tương thích cũ: thang (YYYY-MM) nếu không truyền khoảng ngày
     */
    public function sanXuatDieuPhoi(Request $request): JsonResponse
    {
        return $this->handleApi(function () use ($request) {
            $validated = $request->validate([
                'tu_ngay' => ['sometimes', 'nullable', 'date'],
                'den_ngay' => ['sometimes', 'nullable', 'date', 'after_or_equal:tu_ngay'],
                'thang' => ['sometimes', 'nullable', 'string', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
            ]);

            [$start, $end] = $this->resolvePeriodBounds($validated);
            $stats = $this->sanXuatDieuPhoiTrongKy($start, $end);

            return response()->json(array_merge([
                'tu_ngay' => $start->toDateString(),
                'den_ngay' => $end->toDateString(),
            ], $stats));
        }, 'lấy thống kê Sản xuất & điều phối');
    }

    /**
     * KPI cards tab Tài chính & nhân sự theo tháng.
     *
     * Query: thang (YYYY-MM; mặc định tháng hiện tại)
     */
    public function taiChinhNhanSu(Request $request): JsonResponse
    {
        return $this->handleApi(function () use ($request) {
            $validated = $request->validate([
                'thang' => ['sometimes', 'nullable', 'string', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
            ]);

            $thang = $validated['thang'] ?? Carbon::now(self::TIMEZONE)->format('Y-m');
            [$start, $end] = $this->monthBounds($thang);
            $stats = $this->taiChinhNhanSuTrongThang($start, $end, $thang);
            $nhanSu = $this->tongNhanSu();
            $bieuDoPhongBan = $this->bieuDoNhanSuTheoPhongBan();
            $bieuDoThuChi = $this->bieuDoThuChi12Thang();

            return response()->json(array_merge([
                'thang' => $thang,
                'tu_ngay' => $start->toDateString(),
                'den_ngay' => $end->toDateString(),
                'tong_nhan_su' => $nhanSu['tong_nhan_su'],
                'nhan_su_active' => $nhanSu['nhan_su_active'],
                'bieu_do_nhan_su_theo_phong_ban' => $bieuDoPhongBan,
                'bieu_do_thu_chi_12_thang' => $bieuDoThuChi,
            ], $stats));
        }, 'lấy thống kê Tài chính & nhân sự');
    }

    /**
     * KPI + bảng tab Trang phục theo khoảng ngày.
     *
     * Snapshot (không phụ thuộc kỳ): tổng SP, đang hoạt động, đang cho thuê, HĐ đang thuê.
     * Theo kỳ (created_at / ngày trả / lượt thuê): doanh thu, trả sớm/đúng hạn/quá hạn,
     * top 5 SP, 5 HĐ mới nhất.
     *
     * Query: tu_ngay, den_ngay (YYYY-MM-DD; mặc định tháng hiện tại)
     */
    public function trangPhuc(Request $request): JsonResponse
    {
        return $this->handleApi(function () use ($request) {
            $validated = $request->validate([
                'tu_ngay' => ['sometimes', 'nullable', 'date'],
                'den_ngay' => ['sometimes', 'nullable', 'date', 'after_or_equal:tu_ngay'],
                'thang' => ['sometimes', 'nullable', 'string', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
            ]);

            [$start, $end] = $this->resolvePeriodBounds($validated);
            $stats = $this->trangPhucTrongKy($start, $end);

            return response()->json(array_merge([
                'tu_ngay' => $start->toDateString(),
                'den_ngay' => $end->toDateString(),
            ], $stats));
        }, 'lấy thống kê Trang phục');
    }

    /**
     * @param  array{tu_ngay?: ?string, den_ngay?: ?string, thang?: ?string}  $validated
     * @return array{0: Carbon, 1: Carbon}
     */
    private function resolvePeriodBounds(array $validated): array
    {
        $tuNgay = $validated['tu_ngay'] ?? null;
        $denNgay = $validated['den_ngay'] ?? null;

        if ($tuNgay && $denNgay) {
            $start = Carbon::parse($tuNgay, self::TIMEZONE)->startOfDay();
            $end = Carbon::parse($denNgay, self::TIMEZONE)->endOfDay();

            return [$start, $end];
        }

        $thang = $validated['thang'] ?? Carbon::now(self::TIMEZONE)->format('Y-m');

        return $this->monthBounds($thang);
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function monthBounds(string $thang): array
    {
        $start = Carbon::createFromFormat('Y-m', $thang, self::TIMEZONE)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        return [$start, $end];
    }

    /**
     * Quỹ lương = SUM thực nhận các tháng giao với khoảng ngày.
     *
     * @return array{quy_luong: int, so_nhan_vien: int, da_chot: bool, nguon: string, so_thang: int}
     */
    private function quyLuongTrongKy(Carbon $start, Carbon $end): array
    {
        /** @var TinhLuongController $tinhLuong */
        $tinhLuong = app(TinhLuongController::class);

        $cursor = $start->copy()->startOfMonth();
        $last = $end->copy()->startOfMonth();

        $total = 0;
        $soNhanVienMax = 0;
        $allChot = true;
        $nguonParts = [];
        $soThang = 0;

        while ($cursor->lte($last)) {
            $thang = $cursor->format('Y-m');
            $row = $tinhLuong->tongQuyLuong($thang);
            $total += (int) ($row['quy_luong'] ?? 0);
            $soNhanVienMax = max($soNhanVienMax, (int) ($row['so_nhan_vien'] ?? 0));
            $allChot = $allChot && ! empty($row['da_chot']);
            $nguonParts[] = (string) ($row['nguon'] ?? '');
            $soThang++;
            $cursor->addMonth();
        }

        $uniqueNguon = array_values(array_unique(array_filter($nguonParts)));
        $nguon = count($uniqueNguon) === 1 ? $uniqueNguon[0] : 'hon_hop';

        return [
            'quy_luong' => $total,
            'so_nhan_vien' => $soNhanVienMax,
            'da_chot' => $soThang > 0 && $allChot,
            'nguon' => $nguon,
            'so_thang' => $soThang,
        ];
    }

    /**
     * Doanh thu SDDV = SUM các lần thanh toán có thoi_gian trong kỳ.
     */
    private function doanhThuSddvTrongKy(Carbon $start, Carbon $end): int
    {
        $row = HopDongSuDungDichVu::query()
            ->toBase()
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN thoi_gian_thanh_toan_lan_1 BETWEEN ? AND ? THEN COALESCE(so_tien_thanh_toan_lan_1, 0) ELSE 0 END), 0)
                + COALESCE(SUM(CASE WHEN thoi_gian_thanh_toan_lan_2 BETWEEN ? AND ? THEN COALESCE(so_tien_thanh_toan_lan_2, 0) ELSE 0 END), 0)
                + COALESCE(SUM(CASE WHEN thoi_gian_thanh_toan_lan_3 BETWEEN ? AND ? THEN COALESCE(so_tien_thanh_toan_lan_3, 0) ELSE 0 END), 0) as total',
                [
                    $start->toDateTimeString(), $end->toDateTimeString(),
                    $start->toDateTimeString(), $end->toDateTimeString(),
                    $start->toDateTimeString(), $end->toDateTimeString(),
                ]
            )
            ->first();

        return (int) ($row->total ?? 0);
    }

    /**
     * Doanh thu cho thuê TP = SUM(tong_tien) HĐ tạo trong kỳ (loại nháp / huỷ).
     */
    private function doanhThuChoThueTrongKy(Carbon $start, Carbon $end): int
    {
        return (int) HopDongChoThueTrangPhuc::query()
            ->whereNotIn('trang_thai', self::HD_EXCLUDED_STATUSES)
            ->whereBetween('created_at', [$start, $end])
            ->sum('tong_tien');
    }

    /**
     * @return array{loi_nhuan_truoc_thue: int, tong_thu_da_duyet: int, tong_chi_da_duyet: int}
     */
    private function loiNhuanTruocThue(Carbon $start, Carbon $end): array
    {
        $base = PhieuThuChi::query()
            ->where('trang_thai', 'da_duyet')
            ->whereBetween('ngay_cap_nhat_trang_thai', [$start, $end]);

        $tongThu = (int) (clone $base)->where('loai', 'thu')->sum('so_tien');
        $tongChi = (int) (clone $base)->where('loai', 'chi')->sum('so_tien');

        return [
            'loi_nhuan_truoc_thue' => $tongThu - $tongChi,
            'tong_thu_da_duyet' => $tongThu,
            'tong_chi_da_duyet' => $tongChi,
        ];
    }

    /**
     * KPI Tài chính & nhân sự theo tháng:
     * - Tổng thu / tổng chi: phiếu thu-chi đã duyệt trong tháng
     * - Lợi nhuận trước thuế = SUM(tong_tien_khach_phai_thanh_toan HĐ SDDV)
     *   + SUM(thanh_tien HĐ thuê TP) − quỹ lương thực nhận
     *
     * @return array{
     *   tong_thu: int,
     *   tong_chi: int,
     *   loi_nhuan_truoc_thue: int,
     *   doanh_thu_sddv: int,
     *   doanh_thu_tp: int,
     *   quy_luong: int,
     *   quy_luong_meta: array{so_nhan_vien: int, da_chot: bool, nguon: string}
     * }
     */
    private function taiChinhNhanSuTrongThang(Carbon $start, Carbon $end, string $thang): array
    {
        $thuChi = $this->loiNhuanTruocThue($start, $end);

        $doanhThuSddv = (int) HopDongSuDungDichVu::query()
            ->whereNotIn('trang_thai', self::HD_EXCLUDED_STATUSES)
            ->whereBetween('created_at', [$start, $end])
            ->sum('tong_tien_khach_phai_thanh_toan');

        $doanhThuTp = (int) HopDongChoThueTrangPhuc::query()
            ->whereNotIn('trang_thai', self::HD_EXCLUDED_STATUSES)
            ->whereBetween('created_at', [$start, $end])
            ->sum('thanh_tien');

        /** @var TinhLuongController $tinhLuong */
        $tinhLuong = app(TinhLuongController::class);
        $quyLuong = $tinhLuong->tongQuyLuong($thang);

        $quyLuongValue = (int) ($quyLuong['quy_luong'] ?? 0);

        return [
            'tong_thu' => $thuChi['tong_thu_da_duyet'],
            'tong_chi' => $thuChi['tong_chi_da_duyet'],
            'doanh_thu_sddv' => $doanhThuSddv,
            'doanh_thu_tp' => $doanhThuTp,
            'quy_luong' => $quyLuongValue,
            'quy_luong_meta' => [
                'so_nhan_vien' => (int) ($quyLuong['so_nhan_vien'] ?? 0),
                'da_chot' => ! empty($quyLuong['da_chot']),
                'nguon' => (string) ($quyLuong['nguon'] ?? ''),
            ],
            'loi_nhuan_truoc_thue' => $doanhThuSddv + $doanhThuTp - $quyLuongValue,
        ];
    }

    /**
     * Phân bổ nhân sự active theo phòng ban × loại NV (full_time / part_time / chưa phân công).
     * Một NV có thể thuộc nhiều phòng ban.
     *
     * @return array{
     *   categories: list<string>,
     *   ids: list<?int>,
     *   full_time: list<int>,
     *   part_time: list<int>,
     *   chua_phan_cong: list<int>
     * }
     */
    private function bieuDoNhanSuTheoPhongBan(): array
    {
        $phongBans = PhongBan::query()
            ->orderBy('ten_phong_ban')
            ->get(['id', 'ten_phong_ban']);

        $emptyBucket = ['full_time' => 0, 'part_time' => 0, 'chua_phan_cong' => 0];
        $countMap = [];
        foreach ($phongBans as $pb) {
            $countMap[(int) $pb->id] = $emptyBucket;
        }
        $chuaPhanPhong = $emptyBucket;

        $nhanViens = NhanVien::query()
            ->whereHas('user', fn ($q) => $q->where('status', 'active'))
            ->get(['id', 'phong_ban_ids', 'loai_nhan_vien']);

        foreach ($nhanViens as $nv) {
            $loai = match ($nv->loai_nhan_vien) {
                'full_time' => 'full_time',
                'part_time' => 'part_time',
                default => 'chua_phan_cong',
            };

            $ids = $nv->phong_ban_ids ?? [];
            if (! is_array($ids) || $ids === []) {
                $chuaPhanPhong[$loai]++;
                continue;
            }

            $matched = false;
            foreach ($ids as $pbId) {
                $pbId = (int) $pbId;
                if (array_key_exists($pbId, $countMap)) {
                    $countMap[$pbId][$loai]++;
                    $matched = true;
                }
            }
            if (! $matched) {
                $chuaPhanPhong[$loai]++;
            }
        }

        $categories = [];
        $ids = [];
        $fullTime = [];
        $partTime = [];
        $chuaPhanCong = [];

        foreach ($phongBans as $pb) {
            $id = (int) $pb->id;
            $bucket = $countMap[$id] ?? $emptyBucket;
            $categories[] = $pb->ten_phong_ban ?: ('PB #'.$id);
            $ids[] = $id;
            $fullTime[] = $bucket['full_time'];
            $partTime[] = $bucket['part_time'];
            $chuaPhanCong[] = $bucket['chua_phan_cong'];
        }

        $hasChuaPhanPhong = array_sum($chuaPhanPhong) > 0;
        if ($hasChuaPhanPhong || $categories === []) {
            $categories[] = 'Chưa phân phòng';
            $ids[] = null;
            $fullTime[] = $chuaPhanPhong['full_time'];
            $partTime[] = $chuaPhanPhong['part_time'];
            $chuaPhanCong[] = $chuaPhanPhong['chua_phan_cong'];
        }

        return [
            'categories' => $categories,
            'ids' => $ids,
            'full_time' => $fullTime,
            'part_time' => $partTime,
            'chua_phan_cong' => $chuaPhanCong,
        ];
    }

    /**
     * Thu / chi đã duyệt theo 12 tháng gần nhất (ngay_cap_nhat_trang_thai).
     *
     * @return array{categories: list<string>, tong_thu: list<int>, tong_chi: list<int>}
     */
    private function bieuDoThuChi12Thang(): array
    {
        $end = Carbon::now(self::TIMEZONE)->endOfMonth();
        $start = $end->copy()->subMonths(11)->startOfMonth();

        $rows = PhieuThuChi::query()
            ->where('trang_thai', 'da_duyet')
            ->whereBetween('ngay_cap_nhat_trang_thai', [$start, $end])
            ->toBase()
            ->selectRaw(
                "DATE_FORMAT(ngay_cap_nhat_trang_thai, '%Y-%m') as thang,
                 loai,
                 COALESCE(SUM(so_tien), 0) as tong"
            )
            ->groupBy('thang', 'loai')
            ->get();

        $thuByMonth = [];
        $chiByMonth = [];
        foreach ($rows as $row) {
            $key = (string) $row->thang;
            if ($row->loai === 'thu') {
                $thuByMonth[$key] = (int) $row->tong;
            } elseif ($row->loai === 'chi') {
                $chiByMonth[$key] = (int) $row->tong;
            }
        }

        $categories = [];
        $tongThu = [];
        $tongChi = [];

        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            $key = $cursor->format('Y-m');
            $categories[] = $cursor->format('m/Y');
            $tongThu[] = $thuByMonth[$key] ?? 0;
            $tongChi[] = $chiByMonth[$key] ?? 0;
            $cursor->addMonth();
        }

        return [
            'categories' => $categories,
            'tong_thu' => $tongThu,
            'tong_chi' => $tongChi,
        ];
    }

    /**
     * @return array{so_hop_dong_ky: int, so_hop_dong_sddv_ky: int, so_hop_dong_cho_thue_ky: int}
     */
    private function hopDongKyTrongKy(Carbon $start, Carbon $end): array
    {
        $sddv = (int) HopDongSuDungDichVu::query()
            ->whereNotIn('trang_thai', self::HD_EXCLUDED_STATUSES)
            ->whereBetween('created_at', [$start, $end])
            ->count();

        $choThue = (int) HopDongChoThueTrangPhuc::query()
            ->whereNotIn('trang_thai', self::HD_EXCLUDED_STATUSES)
            ->whereBetween('created_at', [$start, $end])
            ->count();

        return [
            'so_hop_dong_ky' => $sddv + $choThue,
            'so_hop_dong_sddv_ky' => $sddv,
            'so_hop_dong_cho_thue_ky' => $choThue,
        ];
    }

    /**
     * KPI Sản xuất & điều phối: HĐ SDDV ký trong kỳ + buổi chụp + phân loại theo
     * thong_tin_dieu_phoi.trang_thai_dieu_phoi (fallback ket_qua_hop_dong.trang_thai).
     *
     * @return array{
     *   so_hop_dong_sddv_ky: int,
     *   so_buoi_chup: int,
     *   so_hd_tien_ky: int,
     *   so_hd_hau_ky: int,
     *   so_hd_gui_in: int,
     *   so_hd_hoan_tat_san_xuat: int
     * }
     */
    private function sanXuatDieuPhoiTrongKy(Carbon $start, Carbon $end): array
    {
        $rows = HopDongSuDungDichVu::query()
            ->whereNotIn('trang_thai', self::HD_EXCLUDED_STATUSES)
            ->whereBetween('created_at', [$start, $end])
            ->get(['id', 'thong_tin_dieu_phoi', 'ket_qua_hop_dong']);

        $soBuoiChup = 0;
        $soHdTienKy = 0;
        $soHdHauKy = 0;
        $soHdGuiIn = 0;
        $soHdHoanTat = 0;

        foreach ($rows as $row) {
            $soBuoiChup += count(HopDongSuDungDichVu::normalizeDieuPhoiSessions($row->thong_tin_dieu_phoi));

            $status = HopDongSuDungDichVu::trangThaiDieuPhoi($row->thong_tin_dieu_phoi);
            if ($status === null) {
                $ketQua = is_array($row->ket_qua_hop_dong) ? $row->ket_qua_hop_dong : [];
                $fromKetQua = $ketQua['trang_thai']['gia_tri'] ?? null;
                $status = ($fromKetQua === null || $fromKetQua === '')
                    ? null
                    : (string) $fromKetQua;
            }

            match ($status) {
                HopDongSuDungDichVu::TRANG_THAI_DIEU_PHOI_TIEN_KY => $soHdTienKy++,
                HopDongSuDungDichVu::TRANG_THAI_DIEU_PHOI_HAU_KY => $soHdHauKy++,
                HopDongSuDungDichVu::TRANG_THAI_DIEU_PHOI_GUI_IN => $soHdGuiIn++,
                HopDongSuDungDichVu::TRANG_THAI_DIEU_PHOI_HOAN_TAT_SAN_XUAT => $soHdHoanTat++,
                default => null,
            };
        }

        return [
            'so_hop_dong_sddv_ky' => $rows->count(),
            'so_buoi_chup' => $soBuoiChup,
            'so_hd_tien_ky' => $soHdTienKy,
            'so_hd_hau_ky' => $soHdHauKy,
            'so_hd_gui_in' => $soHdGuiIn,
            'so_hd_hoan_tat_san_xuat' => $soHdHoanTat,
        ];
    }

    /**
     * KPI + bảng Trang phục.
     *
     * @return array{
     *   tong_trang_phuc: int,
     *   so_dang_hoat_dong: int,
     *   so_dang_cho_thue: int,
     *   so_hd_dang_cho_thue: int,
     *   doanh_thu_hd: int,
     *   so_hd_tra_som: int,
     *   so_hd_dung_han: int,
     *   so_hd_qua_han: int,
     *   top_san_pham: list<array{id: int, ma_san_pham: ?string, name: string, value: int}>,
     *   hop_dong_moi_nhat: list<array{
     *     id: int,
     *     ma_hop_dong: ?string,
     *     ten_khach_hang: ?string,
     *     ngay_thue: ?string,
     *     ngay_tra_du_kien: ?string,
     *     ngay_tra_chinh_thuc: ?string,
     *     tong_tien: int,
     *     trang_thai: ?string,
     *     hoan_tra_type: string,
     *     hoan_tra_label: string
     *   }>
     * }
     */
    private function trangPhucTrongKy(Carbon $start, Carbon $end): array
    {
        $tongTrangPhuc = (int) TrangPhuc::query()->count();
        $soDangHoatDong = (int) TrangPhuc::query()->where('trang_thai', 1)->count();
        $soDangChoThue = (int) TrangPhuc::query()->where('tinh_trang', 'dang_cho_thue')->count();

        $soHdDangChoThue = (int) HopDongChoThueTrangPhuc::query()
            ->whereIn('trang_thai', ['dang_thue', 'qua_han'])
            ->count();

        $doanhThuHd = $this->doanhThuChoThueTrongKy($start, $end);

        $hoanTra = $this->trangPhucHoanTraTrongKy($start, $end);
        $topSanPham = $this->trangPhucTopSanPhamTrongKy($start, $end);
        $hopDongMoiNhat = $this->trangPhucHopDongMoiNhatTrongKy($start, $end);

        return [
            'tong_trang_phuc' => $tongTrangPhuc,
            'so_dang_hoat_dong' => $soDangHoatDong,
            'so_dang_cho_thue' => $soDangChoThue,
            'so_hd_dang_cho_thue' => $soHdDangChoThue,
            'doanh_thu_hd' => $doanhThuHd,
            'so_hd_tra_som' => $hoanTra['tra_som'],
            'so_hd_dung_han' => $hoanTra['dung_han'],
            'so_hd_qua_han' => $hoanTra['qua_han'],
            'top_san_pham' => $topSanPham,
            'hop_dong_moi_nhat' => $hopDongMoiNhat,
        ];
    }

    /**
     * Phân loại hoàn trả theo ngày trả chính thức trong kỳ.
     * Quá hạn = trả muộn (đã trả) + đang quá hạn (chưa trả, hạn trong kỳ).
     *
     * @return array{tra_som: int, dung_han: int, qua_han: int}
     */
    private function trangPhucHoanTraTrongKy(Carbon $start, Carbon $end): array
    {
        $traSom = 0;
        $dungHan = 0;
        $traMuon = 0;

        $returned = HopDongChoThueTrangPhuc::query()
            ->whereNotIn('trang_thai', self::HD_EXCLUDED_STATUSES)
            ->whereNotNull('ngay_tra_chinh_thuc')
            ->whereNotNull('ngay_tra_du_kien')
            ->whereBetween('ngay_tra_chinh_thuc', [$start->toDateString(), $end->toDateString()])
            ->get(['ngay_tra_du_kien', 'ngay_tra_chinh_thuc']);

        foreach ($returned as $row) {
            $duKien = $row->ngay_tra_du_kien?->toDateString();
            $chinhThuc = $row->ngay_tra_chinh_thuc?->toDateString();
            if ($duKien === null || $chinhThuc === null) {
                continue;
            }
            if ($chinhThuc < $duKien) {
                $traSom++;
            } elseif ($chinhThuc > $duKien) {
                $traMuon++;
            } else {
                $dungHan++;
            }
        }

        $today = Carbon::now(self::TIMEZONE)->toDateString();
        $dangQuaHan = (int) HopDongChoThueTrangPhuc::query()
            ->whereNotIn('trang_thai', array_merge(self::HD_EXCLUDED_STATUSES, ['hoan_thanh', 'da_tra']))
            ->whereNull('ngay_tra_chinh_thuc')
            ->whereNotNull('ngay_tra_du_kien')
            ->where('ngay_tra_du_kien', '<', $today)
            ->whereBetween('ngay_tra_du_kien', [$start->toDateString(), $end->toDateString()])
            ->count();

        return [
            'tra_som' => $traSom,
            'dung_han' => $dungHan,
            'qua_han' => $traMuon + $dangQuaHan,
        ];
    }

    /**
     * Top 5 sản phẩm theo số lượt cho thuê trong kỳ (pivot.ngay_bat_dau).
     *
     * @return list<array{id: int, ma_san_pham: ?string, name: string, value: int}>
     */
    private function trangPhucTopSanPhamTrongKy(Carbon $start, Carbon $end): array
    {
        $rows = HopDongChoThueTrangPhucSanPhamChoThue::query()
            ->from('hop_dong_cho_thue_trang_phuc_san_pham_cho_thue as sp')
            ->join('hop_dong_cho_thue_trang_phuc as hd', 'hd.id', '=', 'sp.hop_dong_id')
            ->leftJoin('trang_phuc as tp', 'tp.id', '=', 'sp.san_pham_id')
            ->whereNotIn('hd.trang_thai', self::HD_EXCLUDED_STATUSES)
            ->whereBetween('sp.ngay_bat_dau', [$start->toDateString(), $end->toDateString()])
            ->whereNotNull('sp.san_pham_id')
            ->selectRaw('sp.san_pham_id as id, tp.ma_san_pham, tp.ten_san_pham, COUNT(*) as luot_thue')
            ->groupBy('sp.san_pham_id', 'tp.ma_san_pham', 'tp.ten_san_pham')
            ->orderByDesc('luot_thue')
            ->limit(5)
            ->get();

        return $rows->map(fn ($row) => [
            'id' => (int) $row->id,
            'ma_san_pham' => $row->ma_san_pham,
            'name' => $row->ten_san_pham ?: ($row->ma_san_pham ?: ('SP #'.$row->id)),
            'value' => (int) $row->luot_thue,
        ])->all();
    }

    /**
     * 5 hợp đồng mới nhất trong kỳ (theo created_at).
     *
     * @return list<array{
     *   id: int,
     *   ma_hop_dong: ?string,
     *   ten_khach_hang: ?string,
     *   ngay_thue: ?string,
     *   ngay_tra_du_kien: ?string,
     *   ngay_tra_chinh_thuc: ?string,
     *   tong_tien: int,
     *   trang_thai: ?string,
     *   hoan_tra_type: string,
     *   hoan_tra_label: string
     * }>
     */
    private function trangPhucHopDongMoiNhatTrongKy(Carbon $start, Carbon $end): array
    {
        $rows = HopDongChoThueTrangPhuc::query()
            ->whereNotIn('trang_thai', self::HD_EXCLUDED_STATUSES)
            ->whereBetween('created_at', [$start, $end])
            ->orderByDesc('created_at')
            ->limit(5)
            ->get([
                'id',
                'ma_hop_dong',
                'ten_khach_hang',
                'ngay_thue',
                'ngay_tra_du_kien',
                'ngay_tra_chinh_thuc',
                'tong_tien',
                'trang_thai',
                'created_at',
            ]);

        $today = Carbon::now(self::TIMEZONE)->toDateString();

        return $rows->map(function ($row) use ($today) {
            [$type, $label] = $this->classifyHoanTraStatus(
                $row->ngay_tra_du_kien?->toDateString(),
                $row->ngay_tra_chinh_thuc?->toDateString(),
                $today,
            );

            return [
                'id' => (int) $row->id,
                'ma_hop_dong' => $row->ma_hop_dong,
                'ten_khach_hang' => $row->ten_khach_hang,
                'ngay_thue' => $row->ngay_thue?->toDateString(),
                'ngay_tra_du_kien' => $row->ngay_tra_du_kien?->toDateString(),
                'ngay_tra_chinh_thuc' => $row->ngay_tra_chinh_thuc?->toDateString(),
                'tong_tien' => (int) ($row->tong_tien ?? 0),
                'trang_thai' => $row->trang_thai,
                'hoan_tra_type' => $type,
                'hoan_tra_label' => $label,
            ];
        })->all();
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function classifyHoanTraStatus(?string $duKien, ?string $chinhThuc, string $today): array
    {
        if ($duKien === null || $duKien === '') {
            return ['muted', '—'];
        }

        if ($chinhThuc !== null && $chinhThuc !== '') {
            if ($chinhThuc < $duKien) {
                return ['early', 'Trả sớm'];
            }
            if ($chinhThuc > $duKien) {
                return ['late', 'Trả muộn'];
            }

            return ['ontime', 'Trả đúng hạn'];
        }

        if ($duKien < $today) {
            return ['overdue', 'Quá hạn'];
        }
        if ($duKien > $today) {
            return ['remaining', 'Còn hạn'];
        }

        return ['today', 'Hôm nay hoàn trả'];
    }

    /**
     * Khách hàng unique theo SĐT (9 số cuối) từ note + HĐ SDDV + HĐ cho thuê.
     *
     * @return array{
     *   tong_khach_hang: int,
     *   tu_note_khach_moi: int,
     *   tu_hop_dong_sddv: int,
     *   tu_hop_dong_cho_thue: int,
     *   so_note_khach_moi: int,
     *   so_hop_dong_sddv: int,
     *   so_hop_dong_cho_thue: int
     * }
     */
    private function tongKhachHang(): array
    {
        $keys = [];
        $fromNote = [];
        $fromSddv = [];
        $fromChoThue = [];

        $notePhones = KhachHangNoteKhachMoi::query()->pluck('sdt');
        foreach ($notePhones as $sdt) {
            $key = $this->customerKey((string) $sdt, 'note', null);
            if ($key === null) {
                continue;
            }
            $keys[$key] = true;
            $fromNote[$key] = true;
        }

        $sddvRows = HopDongSuDungDichVu::query()
            ->get(['id', 'sdt_khach_hang', 'thong_tin_hop_dong']);
        foreach ($sddvRows as $row) {
            $sdt = trim((string) $row->sdt_khach_hang);
            if ($sdt === '') {
                $info = is_array($row->thong_tin_hop_dong) ? $row->thong_tin_hop_dong : [];
                $sdt = trim((string) ($info['soDienThoai'] ?? $info['so_dien_thoai'] ?? $info['sdt'] ?? ''));
            }
            $key = $this->customerKey($sdt, 'sddv', (int) $row->id);
            if ($key === null) {
                continue;
            }
            $keys[$key] = true;
            $fromSddv[$key] = true;
        }

        $choThuePhones = HopDongChoThueTrangPhuc::query()->pluck('sdt_khach_hang', 'id');
        foreach ($choThuePhones as $id => $sdt) {
            $key = $this->customerKey((string) $sdt, 'cho_thue', (int) $id);
            if ($key === null) {
                continue;
            }
            $keys[$key] = true;
            $fromChoThue[$key] = true;
        }

        return [
            'tong_khach_hang' => count($keys),
            'tu_note_khach_moi' => count($fromNote),
            'tu_hop_dong_sddv' => count($fromSddv),
            'tu_hop_dong_cho_thue' => count($fromChoThue),
            'so_note_khach_moi' => (int) KhachHangNoteKhachMoi::query()->count(),
            'so_hop_dong_sddv' => (int) HopDongSuDungDichVu::query()
                ->whereNotIn('trang_thai', self::HD_DRAFT_STATUSES)
                ->count(),
            'so_hop_dong_cho_thue' => (int) HopDongChoThueTrangPhuc::query()
                ->whereNotIn('trang_thai', self::HD_DRAFT_STATUSES)
                ->count(),
        ];
    }

    private function customerKey(string $sdt, string $source, ?int $id): ?string
    {
        $digits = preg_replace('/\D+/', '', $sdt) ?? '';
        if (strlen($digits) >= 9) {
            return 'sdt:'.substr($digits, -9);
        }

        $trimmed = trim($sdt);
        if ($trimmed !== '') {
            return 'raw:'.$trimmed;
        }

        if ($id !== null) {
            return "no-sdt:{$source}:{$id}";
        }

        return null;
    }

    /**
     * Note khách mới trong kỳ + tỷ lệ chốt = HĐ SDDV ký / note đã đến × 100.
     *
     * @return array{tong_note_khach_moi: int, so_note_da_den: int, ty_le_chot: float}
     */
    private function noteKhachMoiTrongKy(Carbon $start, Carbon $end, int $soHopDongSddvKy): array
    {
        $tongNote = (int) KhachHangNoteKhachMoi::query()
            ->whereBetween('created_at', [$start, $end])
            ->count();

        $soDaDen = (int) KhachHangNoteKhachMoi::query()
            ->where('trang_thai', 'da_den')
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('ngay_den_thuc_te', [$start->toDateString(), $end->toDateString()])
                    ->orWhere(function ($inner) use ($start, $end) {
                        $inner->whereNull('ngay_den_thuc_te')
                            ->whereBetween('updated_at', [$start, $end]);
                    });
            })
            ->count();

        $tyLeChot = $soDaDen > 0
            ? round(($soHopDongSddvKy / $soDaDen) * 100, 1)
            : 0.0;

        return [
            'tong_note_khach_moi' => $tongNote,
            'so_note_da_den' => $soDaDen,
            'ty_le_chot' => $tyLeChot,
        ];
    }

    /**
     * @return array{tong_cp_quang_cao: int, tong_lead_quang_cao: int, cpl_trung_binh: int}
     */
    private function quangCaoTrongKy(Carbon $start, Carbon $end): array
    {
        $row = ReportQuangCao::query()
            ->whereDate('ngay', '>=', $start->toDateString())
            ->whereDate('ngay', '<=', $end->toDateString())
            ->toBase()
            ->selectRaw(implode(', ', [
                'COALESCE(SUM(cpqc_tiktok), 0) + COALESCE(SUM(cpqc_fb), 0) + COALESCE(SUM(cpqc_google), 0) as tong_cp',
                'COALESCE(SUM(kh_tiktok), 0) + COALESCE(SUM(kh_fb), 0) + COALESCE(SUM(kh_google), 0) as tong_kh',
            ]))
            ->first();

        $tongCp = (int) ($row->tong_cp ?? 0);
        $tongKh = (int) ($row->tong_kh ?? 0);
        $cpl = $tongKh > 0 ? (int) round($tongCp / $tongKh) : 0;

        return [
            'tong_cp_quang_cao' => $tongCp,
            'tong_lead_quang_cao' => $tongKh,
            'cpl_trung_binh' => $cpl,
        ];
    }

    /**
     * KPI Marketing — cộng dồn report_quang_cao; CPI/CPL tính lại từ tổng (weighted).
     *
     * @return array{
     *   tong_chi_phi_qc: int,
     *   chi_phi_facebook: int,
     *   chi_phi_tiktok: int,
     *   chi_phi_google: int,
     *   tong_inbox: int,
     *   inbox_facebook: int,
     *   inbox_tiktok: int,
     *   cpi_trung_binh: int,
     *   cpl_trung_binh: int,
     *   cpl_facebook: int,
     *   cpl_tiktok: int,
     *   cpl_google: int,
     *   tong_lead: int,
     *   lead_facebook: int,
     *   lead_tiktok: int,
     *   lead_google: int,
     *   khach_den_tu_hen: int,
     *   lich_hen: int,
     *   ty_le_khach_den_hen: float
     * }
     */
    private function marketingQuangCaoTrongKy(Carbon $start, Carbon $end): array
    {
        // Lọc theo cột `ngay` (DATE) của report_quang_cao — khoảng [tu_ngay, den_ngay]
        $tuNgay = $start->toDateString();
        $denNgay = $end->toDateString();

        $row = ReportQuangCao::query()
            ->whereBetween('ngay', [$tuNgay, $denNgay])
            ->toBase()
            ->selectRaw(implode(', ', [
                'COALESCE(SUM(cpqc_fb), 0) as cpqc_fb',
                'COALESCE(SUM(cpqc_tiktok), 0) as cpqc_tiktok',
                'COALESCE(SUM(cpqc_google), 0) as cpqc_google',
                'COALESCE(SUM(inbox_fb), 0) as inbox_fb',
                'COALESCE(SUM(inbox_tiktok), 0) as inbox_tiktok',
                'COALESCE(SUM(kh_fb), 0) as kh_fb',
                'COALESCE(SUM(kh_tiktok), 0) as kh_tiktok',
                'COALESCE(SUM(kh_google), 0) as kh_google',
                'COALESCE(SUM(lich_hen), 0) as lich_hen',
                'COALESCE(SUM(khach_den_tu_hen), 0) as khach_den_tu_hen',
            ]))
            ->first();

        $cpFb = (int) ($row->cpqc_fb ?? 0);
        $cpTt = (int) ($row->cpqc_tiktok ?? 0);
        $cpGg = (int) ($row->cpqc_google ?? 0);
        $inboxFb = (int) ($row->inbox_fb ?? 0);
        $inboxTt = (int) ($row->inbox_tiktok ?? 0);
        $khFb = (int) ($row->kh_fb ?? 0);
        $khTt = (int) ($row->kh_tiktok ?? 0);
        $khGg = (int) ($row->kh_google ?? 0);
        $lichHen = (int) ($row->lich_hen ?? 0);
        $khachDen = (int) ($row->khach_den_tu_hen ?? 0);

        $tongCp = $cpFb + $cpTt + $cpGg;
        $tongInbox = $inboxFb + $inboxTt;
        $tongLead = $khFb + $khTt + $khGg;
        $cpInbox = $cpFb + $cpTt;

        return [
            'tong_chi_phi_qc' => $tongCp,
            'chi_phi_facebook' => $cpFb,
            'chi_phi_tiktok' => $cpTt,
            'chi_phi_google' => $cpGg,
            'tong_inbox' => $tongInbox,
            'inbox_facebook' => $inboxFb,
            'inbox_tiktok' => $inboxTt,
            'cpi_trung_binh' => $tongInbox > 0 ? (int) round($cpInbox / $tongInbox) : 0,
            'cpl_trung_binh' => $tongLead > 0 ? (int) round($tongCp / $tongLead) : 0,
            'cpl_facebook' => $khFb > 0 ? (int) round($cpFb / $khFb) : 0,
            'cpl_tiktok' => $khTt > 0 ? (int) round($cpTt / $khTt) : 0,
            'cpl_google' => $khGg > 0 ? (int) round($cpGg / $khGg) : 0,
            'tong_lead' => $tongLead,
            'lead_facebook' => $khFb,
            'lead_tiktok' => $khTt,
            'lead_google' => $khGg,
            'khach_den_tu_hen' => $khachDen,
            'lich_hen' => $lichHen,
            'ty_le_khach_den_hen' => $lichHen > 0
                ? round($khachDen * 100 / $lichHen, 1)
                : 0.0,
        ];
    }

    /**
     * Series theo ngày trong kỳ — Chi phí / CPL / CPI theo kênh FB, TikTok, Google.
     * CPI Google = 0 (không có inbox_google). CPL/CPI tính lại từ CPQC ÷ KH/inbox trong ngày.
     *
     * @return array{
     *   categories: list<string>,
     *   chi_phi: array{facebook: list<int>, tiktok: list<int>, google: list<int>},
     *   cpl: array{facebook: list<int>, tiktok: list<int>, google: list<int>},
     *   cpi: array{facebook: list<int>, tiktok: list<int>, google: list<int>}
     * }
     */
    private function marketingBieuDoTheoNgay(Carbon $start, Carbon $end): array
    {
        $tuNgay = $start->toDateString();
        $denNgay = $end->toDateString();

        $rows = ReportQuangCao::query()
            ->whereBetween('ngay', [$tuNgay, $denNgay])
            ->toBase()
            ->selectRaw(implode(', ', [
                'ngay',
                'COALESCE(SUM(cpqc_fb), 0) as cpqc_fb',
                'COALESCE(SUM(cpqc_tiktok), 0) as cpqc_tiktok',
                'COALESCE(SUM(cpqc_google), 0) as cpqc_google',
                'COALESCE(SUM(inbox_fb), 0) as inbox_fb',
                'COALESCE(SUM(inbox_tiktok), 0) as inbox_tiktok',
                'COALESCE(SUM(kh_fb), 0) as kh_fb',
                'COALESCE(SUM(kh_tiktok), 0) as kh_tiktok',
                'COALESCE(SUM(kh_google), 0) as kh_google',
            ]))
            ->groupBy('ngay')
            ->orderBy('ngay')
            ->get()
            ->keyBy(fn ($row) => Carbon::parse($row->ngay)->toDateString());

        $categories = [];
        $chiPhi = ['facebook' => [], 'tiktok' => [], 'google' => []];
        $cpl = ['facebook' => [], 'tiktok' => [], 'google' => []];
        $cpi = ['facebook' => [], 'tiktok' => [], 'google' => []];

        $cursor = $start->copy()->startOfDay();
        $last = $end->copy()->startOfDay();

        while ($cursor->lte($last)) {
            $key = $cursor->toDateString();
            $row = $rows->get($key);

            $cpFb = (int) ($row->cpqc_fb ?? 0);
            $cpTt = (int) ($row->cpqc_tiktok ?? 0);
            $cpGg = (int) ($row->cpqc_google ?? 0);
            $inboxFb = (int) ($row->inbox_fb ?? 0);
            $inboxTt = (int) ($row->inbox_tiktok ?? 0);
            $khFb = (int) ($row->kh_fb ?? 0);
            $khTt = (int) ($row->kh_tiktok ?? 0);
            $khGg = (int) ($row->kh_google ?? 0);

            $categories[] = $cursor->format('d/m');
            $chiPhi['facebook'][] = $cpFb;
            $chiPhi['tiktok'][] = $cpTt;
            $chiPhi['google'][] = $cpGg;
            $cpl['facebook'][] = $khFb > 0 ? (int) round($cpFb / $khFb) : 0;
            $cpl['tiktok'][] = $khTt > 0 ? (int) round($cpTt / $khTt) : 0;
            $cpl['google'][] = $khGg > 0 ? (int) round($cpGg / $khGg) : 0;
            $cpi['facebook'][] = $inboxFb > 0 ? (int) round($cpFb / $inboxFb) : 0;
            $cpi['tiktok'][] = $inboxTt > 0 ? (int) round($cpTt / $inboxTt) : 0;
            $cpi['google'][] = 0;

            $cursor->addDay();
        }

        return [
            'categories' => $categories,
            'chi_phi' => $chiPhi,
            'cpl' => $cpl,
            'cpi' => $cpi,
        ];
    }

    /**
     * Cơ cấu doanh thu theo created_at trong kỳ lọc (SUM tong_tien).
     *
     * @return array{doanh_thu_sddv: int, doanh_thu_cho_thue: int, tong: int}
     */
    private function coCauDoanhThuTheoCreatedAt(Carbon $start, Carbon $end): array
    {
        $sddv = (int) HopDongSuDungDichVu::query()
            ->whereNotIn('trang_thai', self::HD_EXCLUDED_STATUSES)
            ->whereBetween('created_at', [$start, $end])
            ->sum('tong_tien');

        $choThue = (int) HopDongChoThueTrangPhuc::query()
            ->whereNotIn('trang_thai', self::HD_EXCLUDED_STATUSES)
            ->whereBetween('created_at', [$start, $end])
            ->sum('tong_tien');

        return [
            'doanh_thu_sddv' => $sddv,
            'doanh_thu_cho_thue' => $choThue,
            'tong' => $sddv + $choThue,
        ];
    }

    /**
     * Doanh thu & số HĐ theo 12 tháng gần đây (theo created_at).
     *
     * @return array{
     *   categories: list<string>,
     *   doanh_thu_sddv: list<int>,
     *   doanh_thu_cho_thue: list<int>,
     *   so_hop_dong_sddv: list<int>,
     *   so_hop_dong_cho_thue: list<int>
     * }
     */
    private function bieuDoDoanhThu12Thang(): array
    {
        $end = Carbon::now(self::TIMEZONE)->endOfMonth();
        $start = $end->copy()->subMonths(11)->startOfMonth();

        $sddvRows = HopDongSuDungDichVu::query()
            ->whereNotIn('trang_thai', self::HD_EXCLUDED_STATUSES)
            ->whereBetween('created_at', [$start, $end])
            ->toBase()
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as thang, COALESCE(SUM(tong_tien), 0) as doanh_thu, COUNT(*) as so_hd")
            ->groupBy('thang')
            ->get()
            ->keyBy('thang');

        $choThueRows = HopDongChoThueTrangPhuc::query()
            ->whereNotIn('trang_thai', self::HD_EXCLUDED_STATUSES)
            ->whereBetween('created_at', [$start, $end])
            ->toBase()
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as thang, COALESCE(SUM(tong_tien), 0) as doanh_thu, COUNT(*) as so_hd")
            ->groupBy('thang')
            ->get()
            ->keyBy('thang');

        $categories = [];
        $doanhThuSddv = [];
        $doanhThuChoThue = [];
        $soHopDongSddv = [];
        $soHopDongChoThue = [];

        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            $key = $cursor->format('Y-m');
            $label = $cursor->format('m/Y');
            $sddv = $sddvRows->get($key);
            $choThue = $choThueRows->get($key);

            $categories[] = $label;
            $doanhThuSddv[] = (int) ($sddv->doanh_thu ?? 0);
            $doanhThuChoThue[] = (int) ($choThue->doanh_thu ?? 0);
            $soHopDongSddv[] = (int) ($sddv->so_hd ?? 0);
            $soHopDongChoThue[] = (int) ($choThue->so_hd ?? 0);

            $cursor->addMonth();
        }

        return [
            'categories' => $categories,
            'doanh_thu_sddv' => $doanhThuSddv,
            'doanh_thu_cho_thue' => $doanhThuChoThue,
            'so_hop_dong_sddv' => $soHopDongSddv,
            'so_hop_dong_cho_thue' => $soHopDongChoThue,
        ];
    }

    /**
     * @return array{tong_nhan_su: int, nhan_su_active: int}
     */
    private function tongNhanSu(): array
    {
        $base = User::query()->whereHas('nhanVien');

        return [
            'tong_nhan_su' => (int) (clone $base)->count(),
            'nhan_su_active' => (int) (clone $base)->where('status', 'active')->count(),
        ];
    }

    /**
     * Tỷ lệ đến = note tạo trong kỳ có trạng thái đã đến / đã ký HĐ ÷ tổng note tạo trong kỳ.
     *
     * @return array{ty_le_den: float, so_note_den_theo_tao: int}
     */
    private function tyLeDenTrongKy(Carbon $start, Carbon $end, int $tongNote): array
    {
        $soDen = (int) KhachHangNoteKhachMoi::query()
            ->whereBetween('created_at', [$start, $end])
            ->whereIn('trang_thai', ['da_den', 'da_ky_hd'])
            ->count();

        $tyLe = $tongNote > 0 ? round(($soDen / $tongNote) * 100, 1) : 0.0;

        return [
            'ty_le_den' => $tyLe,
            'so_note_den_theo_tao' => $soDen,
        ];
    }

    /**
     * Biểu đồ cột theo trạng thái note khách mới tạo trong kỳ.
     *
     * @return array{categories: list<string>, data: list<int>, keys: list<string>}
     */
    private function bieuDoTrangThaiNoteTrongKy(Carbon $start, Carbon $end): array
    {
        $labels = [
            'cho_hen' => 'Chờ hẹn',
            'da_den' => 'Đã đến',
            'khong_den' => 'Không đến',
            'da_ky_hd' => 'Đã ký HĐ',
            'da_huy' => 'Đã hủy',
        ];

        $rows = KhachHangNoteKhachMoi::query()
            ->whereBetween('created_at', [$start, $end])
            ->toBase()
            ->selectRaw('trang_thai, COUNT(*) as so_luong')
            ->groupBy('trang_thai')
            ->pluck('so_luong', 'trang_thai');

        $categories = [];
        $data = [];
        $keys = [];

        foreach ($labels as $key => $label) {
            $categories[] = $label;
            $data[] = (int) ($rows[$key] ?? 0);
            $keys[] = $key;
        }

        foreach ($rows as $key => $count) {
            if (isset($labels[$key])) {
                continue;
            }
            $categories[] = (string) $key;
            $data[] = (int) $count;
            $keys[] = (string) $key;
        }

        return [
            'categories' => $categories,
            'data' => $data,
            'keys' => $keys,
        ];
    }

    /**
     * Nguồn khách: đủ các bản ghi active trong danh_muc_nguon_khach.
     * Series: note (nguon_khach) + HĐ SDDV (kenh_tiep_can). HĐ TP không còn trong chart.
     *
     * @return array{
     *   categories: list<string>,
     *   note_khach_moi: list<int>,
     *   hop_dong_sddv: list<int>,
     *   hop_dong_tp: list<int>
     * }
     */
    private function bieuDoNguonKhachTrongKy(Carbon $start, Carbon $end): array
    {
        $catalog = DanhMucNguonKhach::query()
            ->where('trang_thai', 'active')
            ->orderBy('ten_nguon_khach')
            ->pluck('ten_nguon_khach')
            ->map(fn ($ten) => trim((string) $ten))
            ->filter()
            ->values()
            ->all();

        $noteRows = KhachHangNoteKhachMoi::query()
            ->whereBetween('created_at', [$start, $end])
            ->toBase()
            ->selectRaw('nguon_khach, COUNT(*) as so_luong')
            ->groupBy('nguon_khach')
            ->get();

        $sddvRows = HopDongSuDungDichVu::query()
            ->whereNotIn('trang_thai', self::HD_EXCLUDED_STATUSES)
            ->whereBetween('created_at', [$start, $end])
            ->toBase()
            ->selectRaw('kenh_tiep_can, COUNT(*) as so_luong')
            ->groupBy('kenh_tiep_can')
            ->get();

        $noteByCatalog = array_fill_keys($catalog, 0);
        $sddvByCatalog = array_fill_keys($catalog, 0);

        foreach ($noteRows as $row) {
            $matched = $this->matchNguonKhachToCatalog($row->nguon_khach ?? null, $catalog);
            if ($matched === null) {
                continue;
            }
            $noteByCatalog[$matched] += (int) $row->so_luong;
        }

        foreach ($sddvRows as $row) {
            $matched = $this->matchNguonKhachToCatalog($row->kenh_tiep_can ?? null, $catalog);
            if ($matched === null) {
                continue;
            }
            $sddvByCatalog[$matched] += (int) $row->so_luong;
        }

        $categories = $catalog;
        // Không có danh mục active → vẫn trả 1 cột trống để FE không lỗi
        if ($categories === []) {
            $categories = ['Không có nguồn'];
            $noteByCatalog = ['Không có nguồn' => 0];
            $sddvByCatalog = ['Không có nguồn' => 0];
        }

        return [
            'categories' => $categories,
            'note_khach_moi' => array_map(fn ($ten) => (int) ($noteByCatalog[$ten] ?? 0), $categories),
            'hop_dong_sddv' => array_map(fn ($ten) => (int) ($sddvByCatalog[$ten] ?? 0), $categories),
            'hop_dong_tp' => array_fill(0, count($categories), 0),
        ];
    }

    /**
     * Map giá trị lưu trên note/HĐ về đúng tên trong danh mục (ưu tiên khớp exact, rồi alias slug).
     *
     * @param  list<string>  $catalog
     */
    private function matchNguonKhachToCatalog(mixed $value, array $catalog): ?string
    {
        $raw = trim((string) ($value ?? ''));
        if ($raw === '' || $catalog === []) {
            return null;
        }

        foreach ($catalog as $ten) {
            if (mb_strtolower($ten) === mb_strtolower($raw)) {
                return $ten;
            }
        }

        $normalized = $this->normalizeNguonKhachLabel($raw);
        foreach ($catalog as $ten) {
            if (mb_strtolower($ten) === mb_strtolower($normalized)) {
                return $ten;
            }
        }

        // Alias gần đúng: "Facebook" → "Facebook Ads" / "Facebook Page" (cộng vào mục đầu khớp prefix)
        foreach ($catalog as $ten) {
            $tenLower = mb_strtolower($ten);
            $normLower = mb_strtolower($normalized);
            if (str_starts_with($tenLower, $normLower) || str_starts_with($normLower, $tenLower)) {
                return $ten;
            }
        }

        return null;
    }

    private function normalizeNguonKhachLabel(mixed $value): string
    {
        $raw = trim((string) ($value ?? ''));
        if ($raw === '') {
            return 'Không xác định';
        }

        $map = [
            'tiktok' => 'TikTok',
            'facebook' => 'Facebook',
            'google' => 'Google',
            'gioi_thieu' => 'Giới thiệu',
            'walk_in' => 'Walk-in',
            'khac' => 'Khác',
            'instagram' => 'Instagram',
            'zalo' => 'Zalo',
            'youtube' => 'YouTube',
            'hotline' => 'Hotline',
            'website' => 'Website',
        ];

        $key = mb_strtolower($raw);

        return $map[$key] ?? $raw;
    }

    /**
     * Top 5 sale theo số HĐ ký và theo doanh thu HĐ ký trong kỳ.
     * SDDV: nguoi_tao_id · TP: nguoi_cho_thue.
     *
     * @return array{
     *   top_sale_so_hd: list<array{id: int, name: string, value: int, so_hd_sddv: int, so_hd_tp: int, doanh_thu: int}>,
     *   top_sale_doanh_thu: list<array{id: int, name: string, value: int, so_hd: int, doanh_thu_sddv: int, doanh_thu_tp: int}>
     * }
     */
    private function xepHangSaleTrongKy(Carbon $start, Carbon $end): array
    {
        $sddvRows = HopDongSuDungDichVu::query()
            ->whereNotIn('trang_thai', self::HD_EXCLUDED_STATUSES)
            ->whereBetween('created_at', [$start, $end])
            ->whereNotNull('nguoi_tao_id')
            ->toBase()
            ->selectRaw('nguoi_tao_id as user_id, COUNT(*) as so_hd, COALESCE(SUM(tong_tien), 0) as doanh_thu')
            ->groupBy('nguoi_tao_id')
            ->get()
            ->keyBy('user_id');

        $tpRows = HopDongChoThueTrangPhuc::query()
            ->whereNotIn('trang_thai', self::HD_EXCLUDED_STATUSES)
            ->whereBetween('created_at', [$start, $end])
            ->whereNotNull('nguoi_cho_thue')
            ->toBase()
            ->selectRaw('nguoi_cho_thue as user_id, COUNT(*) as so_hd, COALESCE(SUM(tong_tien), 0) as doanh_thu')
            ->groupBy('nguoi_cho_thue')
            ->get()
            ->keyBy('user_id');

        $userIds = collect($sddvRows->keys())
            ->merge($tpRows->keys())
            ->unique()
            ->filter()
            ->values()
            ->all();

        $users = User::query()
            ->whereIn('id', $userIds)
            ->get(['id', 'name'])
            ->keyBy('id');

        $merged = collect($userIds)->map(function ($userId) use ($sddvRows, $tpRows, $users) {
            $sddv = $sddvRows->get($userId);
            $tp = $tpRows->get($userId);
            $soHdSddv = (int) ($sddv->so_hd ?? 0);
            $soHdTp = (int) ($tp->so_hd ?? 0);
            $dtSddv = (int) ($sddv->doanh_thu ?? 0);
            $dtTp = (int) ($tp->doanh_thu ?? 0);
            $user = $users->get($userId);

            return [
                'id' => (int) $userId,
                'name' => $user?->name ?: ('User #'.$userId),
                'so_hd' => $soHdSddv + $soHdTp,
                'so_hd_sddv' => $soHdSddv,
                'so_hd_tp' => $soHdTp,
                'doanh_thu' => $dtSddv + $dtTp,
                'doanh_thu_sddv' => $dtSddv,
                'doanh_thu_tp' => $dtTp,
            ];
        });

        $topSoHd = $merged
            ->sort(function ($a, $b) {
                if ($a['so_hd'] === $b['so_hd']) {
                    return $b['doanh_thu'] <=> $a['doanh_thu'];
                }

                return $b['so_hd'] <=> $a['so_hd'];
            })
            ->take(5)
            ->values()
            ->map(fn ($row) => [
                'id' => $row['id'],
                'name' => $row['name'],
                'value' => $row['so_hd'],
                'so_hd_sddv' => $row['so_hd_sddv'],
                'so_hd_tp' => $row['so_hd_tp'],
                'doanh_thu' => $row['doanh_thu'],
            ])
            ->all();

        $topDoanhThu = $merged
            ->sort(function ($a, $b) {
                if ($a['doanh_thu'] === $b['doanh_thu']) {
                    return $b['so_hd'] <=> $a['so_hd'];
                }

                return $b['doanh_thu'] <=> $a['doanh_thu'];
            })
            ->take(5)
            ->values()
            ->map(fn ($row) => [
                'id' => $row['id'],
                'name' => $row['name'],
                'value' => $row['doanh_thu'],
                'so_hd' => $row['so_hd'],
                'doanh_thu_sddv' => $row['doanh_thu_sddv'],
                'doanh_thu_tp' => $row['doanh_thu_tp'],
            ])
            ->all();

        return [
            'top_sale_so_hd' => $topSoHd,
            'top_sale_doanh_thu' => $topDoanhThu,
        ];
    }
}
