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
     * KPI cards tab CEO & Admin theo tháng.
     *
     * Query: thang (YYYY-MM, mặc định tháng hiện tại)
     */
    public function ceoAdmin(Request $request): JsonResponse
    {
        return $this->handleApi(function () use ($request) {
            $validated = $request->validate([
                'thang' => ['sometimes', 'nullable', 'string', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
            ]);

            $thang = $validated['thang'] ?? Carbon::now(self::TIMEZONE)->format('Y-m');
            [$start, $end] = $this->monthBounds($thang);

            $doanhThuSddv = $this->doanhThuSddvTrongKy($start, $end);
            $doanhThuChoThue = $this->doanhThuChoThueTrongKy($start, $end);
            $tongDoanhThu = $doanhThuSddv + $doanhThuChoThue;

            $loiNhuan = $this->loiNhuanTruocThue($start, $end);
            $hopDong = $this->hopDongKyTrongKy($start, $end);
            $khachHang = $this->tongKhachHang();
            $quangCao = $this->quangCaoTrongKy($start, $end);
            $nhanSu = $this->tongNhanSu();

            /** @var TinhLuongController $tinhLuong */
            $tinhLuong = app(TinhLuongController::class);
            $quyLuong = $tinhLuong->tongQuyLuong($thang);

            return response()->json([
                'thang' => $thang,
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
                ],
            ]);
        }, 'lấy thống kê CEO & Admin');
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
