<?php

namespace App\Http\Controllers\Api;

use App\Models\CauHinhJson;
use App\Models\DangKyCaLamViec;
use App\Models\DiemDanh;
use App\Models\IpDiemDanh;
use App\Models\XinNghiPhep;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class DiemDanhController extends BaseApiController
{
    private const TIMEZONE = 'Asia/Ho_Chi_Minh';

    private const GHI_CHU_CHUA_DANG_KY_CA = 'Điểm danh chưa đăng ký ca';

    /**
     * Danh sách điểm danh — phân trang + lọc.
     *
     * Query: page, per_page, keyword, user_id, ngay_lam, tu_ngay, den_ngay
     */
    public function index(Request $request): JsonResponse
    {
        return $this->handleApi(function () use ($request) {
            $validated = $request->validate([
                'page' => ['sometimes', 'integer', 'min:1'],
                'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
                'keyword' => ['sometimes', 'nullable', 'string', 'max:255'],
                'user_id' => ['sometimes', 'nullable', 'integer', 'exists:users,id'],
                'ngay_lam' => ['sometimes', 'nullable', 'date'],
                'tu_ngay' => ['sometimes', 'nullable', 'date'],
                'den_ngay' => ['sometimes', 'nullable', 'date'],
            ]);

            $perPage = $validated['per_page'] ?? 10;
            $keyword = trim((string) ($validated['keyword'] ?? ''));

            $query = DiemDanh::query()
                ->with([
                    'user:id,name,email',
                    'caLam:id,ten_ca,gio_bat_dau,gio_ket_thuc',
                ])
                ->when($keyword !== '', function ($q) use ($keyword) {
                    $q->where(function ($inner) use ($keyword) {
                        $inner->where('ly_do', 'like', "%{$keyword}%")
                            ->orWhere('ghi_chu', 'like', "%{$keyword}%")
                            ->orWhereHas('user', function ($userQuery) use ($keyword) {
                                $userQuery->where('name', 'like', "%{$keyword}%")
                                    ->orWhere('email', 'like', "%{$keyword}%");
                            });
                    });
                })
                ->when(
                    ! empty($validated['user_id']),
                    fn ($q) => $q->where('user_id', $validated['user_id'])
                )
                ->when(
                    ! empty($validated['ngay_lam']),
                    fn ($q) => $q->whereDate('ngay_lam', $validated['ngay_lam'])
                )
                ->when(
                    ! empty($validated['tu_ngay']),
                    fn ($q) => $q->whereDate('ngay_lam', '>=', $validated['tu_ngay'])
                )
                ->when(
                    ! empty($validated['den_ngay']),
                    fn ($q) => $q->whereDate('ngay_lam', '<=', $validated['den_ngay'])
                )
                ->orderByDesc('ngay_lam')
                ->orderByDesc('id');

            return response()->json($query->paginate($perPage));

        }, 'lấy danh sách điểm danh');
    }

    /**
     * Trạng thái điểm danh hôm nay của user đang đăng nhập.
     */
    public function today(Request $request): JsonResponse
    {
        return $this->handleApi(function () use ($request) {
            $today = $this->todayDate();
            $userId = (int) $request->user()->id;

            $diemDanh = DiemDanh::query()
                ->with([
                    'user:id,name,email',
                    'caLam:id,ten_ca,gio_bat_dau,gio_ket_thuc',
                ])
                ->where('user_id', $userId)
                ->whereDate('ngay_lam', $today)
                ->first();

            $dangKy = $this->findDangKyCaHomNay($userId, $today);

            return response()->json([
                'ngay_lam' => $today->toDateString(),
                'has_dang_ky_ca' => $dangKy !== null,
                'dang_ky_ca' => $dangKy,
                'diem_danh' => $diemDanh,
                'can_checkin' => $diemDanh === null || $diemDanh->gio_vao === null,
                'can_checkout' => $diemDanh !== null
                    && $diemDanh->gio_vao !== null
                    && $diemDanh->gio_ra === null,
            ]);

        }, 'lấy điểm danh hôm nay');
    }

    /**
     * Checkin — tạo bản ghi điểm danh trong ngày.
     */
    public function checkin(Request $request): JsonResponse
    {
        return $this->handleApi(function () use ($request) {
            $clientIp = $this->resolveClientIp($request);

            $user = $request->user();
            $now = Carbon::now(self::TIMEZONE);
            $today = $now->copy()->startOfDay();

            $dangKy = $this->resolveDangKyCaHomNay((int) $user->id, $today);
            $caLam = $dangKy?->caLam;
            $chuaDangKyCa = $caLam === null;

            $existing = DiemDanh::query()
                ->where('user_id', $user->id)
                ->whereDate('ngay_lam', $today)
                ->first();

            if ($existing && $existing->gio_vao) {
                throw ValidationException::withMessages([
                    'checkin' => ['Bạn đã checkin hôm nay rồi.'],
                ]);
            }

            if ($chuaDangKyCa) {
                $payload = [
                    'user_id' => $user->id,
                    'ngay_lam' => $today->toDateString(),
                    'ca_lam_id' => null,
                    'gio_vao' => $now->format('Y-m-d H:i:s'),
                    'di_muon' => 'khong',
                    'thoi_gian_di_muon' => 0,
                    'tien_phat_di_muon' => 0,
                    'ly_do' => null,
                    'ghi_chu' => self::GHI_CHU_CHUA_DANG_KY_CA,
                    'ip_checkin' => $clientIp,
                ];
            } else {
                $gioBatDau = $this->combineDateAndTime($today, $caLam->gio_bat_dau);
                $lateMinutes = max(0, (int) $gioBatDau->diffInMinutes($now, false));

                $waiveLate = $this->hasApprovedLeave((int) $user->id, $today, 'di_muon');
                $lyDo = $this->resolveLyDo((int) $user->id, $today);

                $diMuon = (! $waiveLate && $lateMinutes > 0) ? 'co' : 'khong';
                $thoiGianDiMuon = $diMuon === 'co' ? $lateMinutes : 0;
                $tienPhatDiMuon = $diMuon === 'co'
                    ? $this->tinhTienPhat($user, $caLam, $thoiGianDiMuon)
                    : 0;

                $payload = [
                    'user_id' => $user->id,
                    'ngay_lam' => $today->toDateString(),
                    'ca_lam_id' => $caLam->id,
                    'gio_vao' => $now->format('Y-m-d H:i:s'),
                    'di_muon' => $diMuon,
                    'thoi_gian_di_muon' => $thoiGianDiMuon,
                    'tien_phat_di_muon' => $tienPhatDiMuon,
                    'ly_do' => $lyDo,
                    'ghi_chu' => null,
                    'ip_checkin' => $clientIp,
                ];
            }

            if ($existing) {
                $existing->update($payload);
                $diemDanh = $existing->fresh();
            } else {
                $diemDanh = DiemDanh::create($payload);
            }

            $diemDanh->load([
                'user:id,name,email',
                'caLam:id,ten_ca,gio_bat_dau,gio_ket_thuc',
            ]);

            return response()->json($diemDanh, 201);

        }, 'checkin điểm danh');
    }

    /**
     * Checkout — cập nhật giờ ra và tính giờ làm / phạt về sớm.
     */
    public function checkout(Request $request): JsonResponse
    {
        return $this->handleApi(function () use ($request) {
            $clientIp = $this->resolveClientIp($request);

            $user = $request->user();
            $now = Carbon::now(self::TIMEZONE);
            $today = $now->copy()->startOfDay();

            $diemDanh = DiemDanh::query()
                ->where('user_id', $user->id)
                ->whereDate('ngay_lam', $today)
                ->first();

            if (! $diemDanh || ! $diemDanh->gio_vao) {
                throw ValidationException::withMessages([
                    'checkout' => ['Bạn chưa checkin hôm nay.'],
                ]);
            }

            if ($diemDanh->gio_ra) {
                throw ValidationException::withMessages([
                    'checkout' => ['Bạn đã checkout hôm nay rồi.'],
                ]);
            }

            $gioVao = Carbon::parse($diemDanh->gio_vao, self::TIMEZONE);
            if ($now->lt($gioVao)) {
                throw ValidationException::withMessages([
                    'checkout' => ['Giờ checkout không hợp lệ.'],
                ]);
            }

            $chuaDangKyCa = $diemDanh->ca_lam_id === null;

            if ($chuaDangKyCa) {
                $phutLam = (int) $gioVao->diffInMinutes($now);
                $gioLamCoBan = round($phutLam / 60, 2);
                $gioLamTangCa = 0.0;
                [$luongCoBan, $luongTangCa] = $this->tinhLuong($user, $gioLamCoBan, $gioLamTangCa);

                $diemDanh->update([
                    'gio_ra' => $now->format('Y-m-d H:i:s'),
                    've_som' => 'khong',
                    'thoi_gian_ve_som' => 0,
                    'tien_phat_ve_som' => 0,
                    'ghi_chu' => $diemDanh->ghi_chu ?: self::GHI_CHU_CHUA_DANG_KY_CA,
                    'ip_checkout' => $clientIp,
                    'gio_lam_co_ban' => $gioLamCoBan,
                    'gio_lam_tang_ca' => $gioLamTangCa,
                    'luong_co_ban' => $luongCoBan,
                    'luong_tang_ca' => $luongTangCa,
                ]);
            } else {
                $dangKy = $this->resolveDangKyCaHomNay((int) $user->id, $today);
                $caLam = $dangKy?->caLam ?? $diemDanh->caLam;

                if (! $caLam) {
                    throw ValidationException::withMessages([
                        'ca_lam' => ['Không tìm thấy ca làm để checkout.'],
                    ]);
                }

                $gioKetThuc = $this->combineDateAndTime($today, $caLam->gio_ket_thuc);
                $earlyMinutes = max(0, (int) $now->diffInMinutes($gioKetThuc, false));

                $waiveEarly = $this->hasApprovedLeave((int) $user->id, $today, 've_som');
                $veSom = (! $waiveEarly && $earlyMinutes > 0) ? 'co' : 'khong';
                $thoiGianVeSom = $veSom === 'co' ? $earlyMinutes : 0;
                $tienPhatVeSom = $veSom === 'co'
                    ? $this->tinhTienPhat($user, $caLam, $thoiGianVeSom)
                    : 0;

                [$gioLamCoBan, $gioLamTangCa] = $this->tinhGioLam($gioVao, $now, $caLam);
                [$luongCoBan, $luongTangCa] = $this->tinhLuong($user, $gioLamCoBan, $gioLamTangCa);

                $lyDo = $diemDanh->ly_do ?: $this->resolveLyDo((int) $user->id, $today);

                $diemDanh->update([
                    'ca_lam_id' => $caLam->id,
                    'gio_ra' => $now->format('Y-m-d H:i:s'),
                    've_som' => $veSom,
                    'thoi_gian_ve_som' => $thoiGianVeSom,
                    'tien_phat_ve_som' => $tienPhatVeSom,
                    'ly_do' => $lyDo,
                    'ip_checkout' => $clientIp,
                    'gio_lam_co_ban' => $gioLamCoBan,
                    'gio_lam_tang_ca' => $gioLamTangCa,
                    'luong_co_ban' => $luongCoBan,
                    'luong_tang_ca' => $luongTangCa,
                ]);
            }

            $diemDanh->load([
                'user:id,name,email',
                'caLam:id,ten_ca,gio_bat_dau,gio_ket_thuc',
            ]);

            return response()->json($diemDanh->fresh([
                'user:id,name,email',
                'caLam:id,ten_ca,gio_bat_dau,gio_ket_thuc',
            ]));

        }, 'checkout điểm danh');
    }

    private function todayDate(): Carbon
    {
        return Carbon::now(self::TIMEZONE)->startOfDay();
    }

    private function findDangKyCaHomNay(int $userId, Carbon $today): ?DangKyCaLamViec
    {
        return DangKyCaLamViec::query()
            ->with('caLam:id,ten_ca,gio_bat_dau,gio_ket_thuc,trang_thai')
            ->where('nguoi_dung_id', $userId)
            ->whereDate('ngay_lam', $today)
            ->first();
    }

    /**
     * Trả về đăng ký ca hôm nay; null nếu chưa đăng ký và cấu hình cho phép điểm danh không cần ca.
     */
    private function resolveDangKyCaHomNay(int $userId, Carbon $today): ?DangKyCaLamViec
    {
        $dangKy = $this->findDangKyCaHomNay($userId, $today);

        if (! $dangKy || ! $dangKy->caLam) {
            if ($this->yeuCauDangKyCa()) {
                throw ValidationException::withMessages([
                    'ca_lam' => ['Bạn chưa đăng ký ca làm hôm nay nên không thể điểm danh.'],
                ]);
            }

            return null;
        }

        if ($dangKy->caLam->trang_thai === 'khong') {
            throw ValidationException::withMessages([
                'ca_lam' => ['Ca làm đã đăng ký đang không hoạt động. Vui lòng liên hệ quản trị.'],
            ]);
        }

        return $dangKy;
    }

    private function yeuCauDangKyCa(): bool
    {
        return (bool) data_get(
            $this->chamCongConfig(),
            'yeu_cau_dang_ky_ca_moi_duoc_diem_danh.gia_tri',
            false
        );
    }

    private function kiemSoatIp(): bool
    {
        return (bool) data_get(
            $this->chamCongConfig(),
            'kiem_soat_ip_diem_danh.gia_tri',
            false
        );
    }

    /**
     * Lấy IP client khi bật kiểm soát IP; null nếu tắt.
     */
    private function resolveClientIp(Request $request): ?string
    {
        if (! $this->kiemSoatIp()) {
            return null;
        }

        $validated = $request->validate([
            'ip' => ['required', 'string', 'max:45', 'ip'],
        ]);

        $clientIp = $validated['ip'];
        $this->assertIpAllowed($clientIp);

        return $clientIp;
    }

    private function assertIpAllowed(string $clientIp): void
    {
        $allowed = IpDiemDanh::query()
            ->where('trang_thai', 'active')
            ->where('dia_chi_ip', $clientIp)
            ->exists();

        if (! $allowed) {
            throw ValidationException::withMessages([
                'ip' => ["IP {$clientIp} không được phép điểm danh."],
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function chamCongConfig(): array
    {
        $row = CauHinhJson::query()->first();
        $all = is_array($row?->thong_tin_cau_hinh) ? $row->thong_tin_cau_hinh : [];

        return is_array($all['cham_cong_tang_ca'] ?? null) ? $all['cham_cong_tang_ca'] : [];
    }

    private function combineDateAndTime(Carbon $date, mixed $time): Carbon
    {
        $timeStr = substr((string) $time, 0, 8);
        if (strlen($timeStr) === 5) {
            $timeStr .= ':00';
        }

        return Carbon::parse(
            $date->toDateString().' '.$timeStr,
            self::TIMEZONE
        );
    }

    private function hasApprovedLeave(int $userId, Carbon $today, string $loai): bool
    {
        return XinNghiPhep::query()
            ->where('user_id', $userId)
            ->where('trang_thai', 'da_duyet')
            ->where('loai_nghi_phep', $loai)
            ->whereDate('ngay_bat_dau', '<=', $today)
            ->where(function ($q) use ($today) {
                $q->whereNull('ngay_ket_thuc')
                    ->orWhereDate('ngay_ket_thuc', '>=', $today);
            })
            ->exists();
    }

    private function resolveLyDo(int $userId, Carbon $today): ?string
    {
        $leave = XinNghiPhep::query()
            ->where('user_id', $userId)
            ->where('trang_thai', 'da_duyet')
            ->whereIn('loai_nghi_phep', ['di_muon', 've_som'])
            ->whereDate('ngay_bat_dau', '<=', $today)
            ->where(function ($q) use ($today) {
                $q->whereNull('ngay_ket_thuc')
                    ->orWhereDate('ngay_ket_thuc', '>=', $today);
            })
            ->orderByDesc('id')
            ->first();

        return $leave?->ly_do;
    }

    /**
     * Phạt theo tỷ lệ lương ngày / số phút ca.
     */
    private function tinhTienPhat(mixed $user, mixed $caLam, int $phut): float
    {
        if ($phut <= 0) {
            return 0;
        }

        $user->loadMissing('nhanVien');
        $nhanVien = $user->nhanVien;
        if (! $nhanVien) {
            return 0;
        }

        $luongCoBan = $nhanVien->getLuongValue('luong_1_gio');
        $congChuan = (float) ($nhanVien->cong_chuan ?? 0);
        if ($luongCoBan <= 0 || $congChuan <= 0) {
            return 0;
        }

        $ngay = $this->todayDate();
        $gioBatDau = $this->combineDateAndTime($ngay, $caLam->gio_bat_dau);
        $gioKetThuc = $this->combineDateAndTime($ngay, $caLam->gio_ket_thuc);
        $phutCa = max(1, (int) $gioBatDau->diffInMinutes($gioKetThuc));

        $tienMotPhut = ($luongCoBan / $congChuan) / $phutCa;

        return round($tienMotPhut * $phut, 2);
    }

    /**
     * @return array{0: float, 1: float}
     */
    private function tinhGioLam(Carbon $gioVao, Carbon $gioRa, mixed $caLam): array
    {
        $config = $this->chamCongConfig();
        $gioTangCaStr = (string) data_get($config, 'gio_tinh_tang_ca.gia_tri', '18:00');
        $minOtMinutes = (int) data_get($config, 'so_phut_toi_thieu_de_tinh_tang_ca.gia_tri', 30);

        $gioBatDauCa = $this->combineDateAndTime($gioVao->copy()->startOfDay(), $caLam->gio_bat_dau);
        $gioKetThucCa = $this->combineDateAndTime($gioVao->copy()->startOfDay(), $caLam->gio_ket_thuc);
        $mocTangCa = $this->combineDateAndTime($gioVao->copy()->startOfDay(), $gioTangCaStr);

        $start = $gioVao->greaterThan($gioBatDauCa) ? $gioVao->copy() : $gioBatDauCa->copy();
        $end = $gioRa->lessThan($gioKetThucCa) ? $gioRa->copy() : $gioKetThucCa->copy();

        $phutCoBan = $end->greaterThan($start) ? (int) $start->diffInMinutes($end) : 0;
        $gioLamCoBan = round($phutCoBan / 60, 2);

        $otStart = $mocTangCa->greaterThan($gioKetThucCa) ? $mocTangCa->copy() : $gioKetThucCa->copy();
        if ($gioRa->lessThanOrEqualTo($otStart)) {
            return [$gioLamCoBan, 0.0];
        }

        $phutTangCa = (int) $otStart->diffInMinutes($gioRa);
        if ($phutTangCa < $minOtMinutes) {
            return [$gioLamCoBan, 0.0];
        }

        return [$gioLamCoBan, round($phutTangCa / 60, 2)];
    }

    /**
     * @return array{0: float, 1: float}
     */
    private function tinhLuong(mixed $user, float $gioLamCoBan, float $gioLamTangCa): array
    {
        $user->loadMissing('nhanVien');
        $nhanVien = $user->nhanVien;
        if (! $nhanVien) {
            return [0.0, 0.0];
        }

        $luongCoBanThang = $nhanVien->getLuongValue('luong_1_gio');
        $congChuan = (float) ($nhanVien->cong_chuan ?? 0);
        $luongTangCaGio = $nhanVien->getLuongValue('luong_tang_ca_1_gio');

        // Lương cơ bản ngày ≈ (lương tháng / công chuẩn) × (giờ làm cơ bản / 8)
        $luongCoBanNgay = ($luongCoBanThang > 0 && $congChuan > 0 && $gioLamCoBan > 0)
            ? round(($luongCoBanThang / $congChuan) * ($gioLamCoBan / 8), 2)
            : 0.0;

        $luongTangCa = round($luongTangCaGio * $gioLamTangCa, 2);

        return [$luongCoBanNgay, $luongTangCa];
    }
}
