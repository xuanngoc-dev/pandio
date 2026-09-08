<?php

namespace App\Http\Controllers\Api;

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
}
