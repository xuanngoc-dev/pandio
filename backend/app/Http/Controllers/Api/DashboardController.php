<?php

namespace App\Http\Controllers\Api;

use App\Models\DanhMucNguonKhach;
use App\Models\HopDongChoThueTrangPhuc;
use App\Models\HopDongSuDungDichVu;
use App\Models\KhachHangNoteKhachMoi;
use App\Models\PhieuThuChi;
use App\Models\ReportQuangCao;
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
