<?php

namespace App\Http\Controllers\Api;

use App\Models\CauHinhNgayNghi;
use App\Models\DiemDanh;
use App\Models\HopDongChoThueTrangPhuc;
use App\Models\HopDongSuDungDichVu;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class TinhLuongController extends BaseApiController
{
    private const TIMEZONE = 'Asia/Ho_Chi_Minh';

    private const DOW_LABELS = [
        0 => 'CN',
        1 => 'T2',
        2 => 'T3',
        3 => 'T4',
        4 => 'T5',
        5 => 'T6',
        6 => 'T7',
    ];

    private const STAFF_ROLE_KEYS = [
        'tho_make' => 'make',
        'tho_chup' => 'chup',
        'quay_phim' => 'quay_phim',
        'tho_edit' => 'edit',
    ];

    /**
     * Bảng lương chi tiết theo ngày trong tháng của user đang đăng nhập.
     *
     * Query: thang (YYYY-MM, required)
     */
    public function chiTietTheoNgay(Request $request): JsonResponse
    {
        return $this->handleApi(function () use ($request) {
            $validated = $request->validate([
                'thang' => ['required', 'string', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
            ]);

            $user = User::query()
                ->with(['nhanVien:id,user_id,loai_nhan_vien,cong_chuan,luong_thuong_phu_cap'])
                ->select(['id', 'name', 'email'])
                ->findOrFail((int) $request->user()->id);

            $nhanVien = $user->nhanVien;
            if (! $nhanVien) {
                throw ValidationException::withMessages([
                    'nhan_vien' => ['Tài khoản chưa có hồ sơ nhân sự.'],
                ]);
            }

            $payload = $this->buildBangLuongThang($user, $nhanVien, $validated['thang'], includeDays: true);

            return response()->json($payload);
        }, 'lấy bảng lương chi tiết theo ngày');
    }

    /**
     * Lương tổng hợp theo tháng — danh sách nhân viên (phân trang).
     *
     * Query: thang (YYYY-MM, required), page, per_page, keyword
     */
    public function tongHop(Request $request): JsonResponse
    {
        return $this->handleApi(function () use ($request) {
            $validated = $request->validate([
                'thang' => ['required', 'string', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
                'page' => ['sometimes', 'integer', 'min:1'],
                'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
                'keyword' => ['sometimes', 'nullable', 'string', 'max:255'],
            ]);

            $perPage = $validated['per_page'] ?? 10;
            $keyword = trim((string) ($validated['keyword'] ?? ''));

            $query = User::query()
                ->with(['nhanVien:id,user_id,loai_nhan_vien,cong_chuan,luong_thuong_phu_cap'])
                ->select(['id', 'name', 'email', 'phone', 'status'])
                ->where('status', 'active')
                ->whereHas('nhanVien')
                ->when($keyword !== '', function ($q) use ($keyword) {
                    $q->where(function ($inner) use ($keyword) {
                        $inner->where('name', 'like', "%{$keyword}%")
                            ->orWhere('email', 'like', "%{$keyword}%")
                            ->orWhere('phone', 'like', "%{$keyword}%");
                    });
                })
                ->orderBy('name');

            $paginator = $query->paginate($perPage);
            $thang = $validated['thang'];

            $items = collect($paginator->items())->map(function (User $user) use ($thang) {
                $nhanVien = $user->nhanVien;
                $payload = $this->buildBangLuongThang($user, $nhanVien, $thang, includeDays: false);
                $tong = $payload['tong_ket'];

                // Thưởng phát sinh sản xuất (make/chụp/quay); hậu kỳ = edit.
                $thuong = round(
                    (float) $tong['san_xuat_make']
                    + (float) $tong['san_xuat_chup']
                    + (float) $tong['san_xuat_quay_phim'],
                    2
                );
                $hauKy = round((float) $tong['san_xuat_edit'], 2);

                return [
                    'user_id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'loai_nhan_vien' => $nhanVien?->loai_nhan_vien,
                    'luong' => [
                        'luong_cung' => $payload['nhan_vien']['luong_co_dinh']['luong_cung'],
                        'luong_mem' => $payload['nhan_vien']['luong_co_dinh']['luong_mem'],
                        'phu_cap' => $tong['tong_phu_cap'],
                    ],
                    'phat_sinh' => [
                        'luong_theo_gio' => $tong['tong_luong_theo_gio'],
                        'luong_tang_ca' => $tong['tong_tang_ca'],
                        'thuong' => $thuong,
                        'chuyen_can' => $tong['thuong_chuyen_can'],
                        'hoa_hong' => $tong['tong_hoa_hong'],
                        'hau_ky' => $hauKy,
                    ],
                    'khau_tru' => [
                        'di_muon' => $tong['tien_phat_di_muon'],
                        've_som' => $tong['tien_phat_ve_som'],
                        'phat_sinh' => $tong['phat_phat_sinh'],
                    ],
                    'thuc_nhan' => $tong['thuc_nhan'],
                ];
            })->values();

            return response()->json([
                'thang' => $thang,
                'data' => $items,
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ]);
        }, 'lấy lương tổng hợp theo tháng');
    }

    /**
     * @return array<string, mixed>
     */
    private function buildBangLuongThang(User $user, mixed $nhanVien, string $thang, bool $includeDays = true): array
    {
        $start = Carbon::createFromFormat('Y-m', $thang, self::TIMEZONE)->startOfMonth();
        $end = $start->copy()->endOfMonth();
        $tuNgay = $start->toDateString();
        $denNgay = $end->toDateString();
        $userId = (int) $user->id;

        $records = DiemDanh::query()
            ->where('user_id', $userId)
            ->whereDate('ngay_lam', '>=', $tuNgay)
            ->whereDate('ngay_lam', '<=', $denNgay)
            ->orderBy('ngay_lam')
            ->get()
            ->keyBy(fn (DiemDanh $item) => $item->ngay_lam?->format('Y-m-d'));

        $luongCung = $nhanVien->getLuongValue('luong_cung');
        $luongMem = $nhanVien->getLuongValue('luong_mem');
        $loaiNhanVien = (string) ($nhanVien->loai_nhan_vien ?? '');
        $phuCap = [
            'phu_cap' => $nhanVien->getLuongValue('phu_cap'),
            'phu_cap_xang' => $nhanVien->getLuongValue('phu_cap_xang'),
            'phu_cap_an_trua' => $nhanVien->getLuongValue('phu_cap_an_trua'),
            'phu_cap_dien_thoai' => $nhanVien->getLuongValue('phu_cap_dien_thoai'),
            'phu_cap_nha_o' => $nhanVien->getLuongValue('phu_cap_nha_o'),
        ];
        $holidayDates = $this->activeHolidayDates($tuNgay, $denNgay);

        $hoaHongTpByDate = $this->hoaHongTrangPhucByDate($userId, $tuNgay, $denNgay, $nhanVien->getLuongValue('hoa_hong_hop_dong_trang_phuc'));
        $hoaHongSddvByDate = $this->hoaHongSddvByDate($userId, $tuNgay, $denNgay, $nhanVien->getLuongValue('hoa_hong_hop_dong_sddv'));
        $sanXuatByDate = $this->sanXuatByDate($userId, $tuNgay, $denNgay, $nhanVien);

        $days = [];
        $soNgayLamThuBayChuNhat = 0;
        $tong = [
            'gio_lam_co_ban' => 0.0,
            'gio_lam_tang_ca' => 0.0,
            'tong_luong_theo_gio' => 0.0,
            'tong_tang_ca' => 0.0,
            'hoa_hong_hd_tp' => 0.0,
            'hoa_hong_hd_sddv' => 0.0,
            'san_xuat_make' => 0.0,
            'san_xuat_chup' => 0.0,
            'san_xuat_quay_phim' => 0.0,
            'san_xuat_edit' => 0.0,
            'tien_phat_di_muon' => 0.0,
            'tien_phat_ve_som' => 0.0,
            'phat_phat_sinh' => 0.0,
        ];

        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            $dateKey = $date->toDateString();
            $record = $records->get($dateKey);
            $hoaHongTp = round((float) ($hoaHongTpByDate[$dateKey] ?? 0), 2);
            $hoaHongSddv = round((float) ($hoaHongSddvByDate[$dateKey] ?? 0), 2);
            $sanXuat = $sanXuatByDate[$dateKey] ?? $this->emptySanXuat();

            $gioLamCoBan = round((float) ($record?->gio_lam_co_ban ?? 0), 2);
            $gioLamTangCa = round((float) ($record?->gio_lam_tang_ca ?? 0), 2);
            $luongCoBan = round((float) ($record?->luong_co_ban ?? 0), 2);
            $luongTangCa = round((float) ($record?->luong_tang_ca ?? 0), 2);
            $tienPhatDiMuon = round((float) ($record?->tien_phat_di_muon ?? 0), 2);
            $tienPhatVeSom = round((float) ($record?->tien_phat_ve_som ?? 0), 2);

            if ($record !== null && $date->isWeekend()) {
                $soNgayLamThuBayChuNhat++;
            }

            $tong['gio_lam_co_ban'] += $gioLamCoBan;
            $tong['gio_lam_tang_ca'] += $gioLamTangCa;
            $tong['tong_luong_theo_gio'] += $luongCoBan;
            $tong['tong_tang_ca'] += $luongTangCa;
            $tong['hoa_hong_hd_tp'] += $hoaHongTp;
            $tong['hoa_hong_hd_sddv'] += $hoaHongSddv;
            $tong['san_xuat_make'] += $sanXuat['make'];
            $tong['san_xuat_chup'] += $sanXuat['chup'];
            $tong['san_xuat_quay_phim'] += $sanXuat['quay_phim'];
            $tong['san_xuat_edit'] += $sanXuat['edit'];
            $tong['tien_phat_di_muon'] += $tienPhatDiMuon;
            $tong['tien_phat_ve_som'] += $tienPhatVeSom;

            if ($includeDays) {
                $days[] = [
                    'ngay' => $dateKey,
                    'ngay_trong_thang' => (int) $date->day,
                    'thu' => self::DOW_LABELS[$date->dayOfWeek] ?? '',
                    'is_weekend' => $date->isWeekend(),
                    'co_diem_danh' => $record !== null,
                    'gio_vao' => $record?->gio_vao,
                    'gio_ra' => $record?->gio_ra,
                    'gio_lam_co_ban' => $gioLamCoBan,
                    'gio_lam_tang_ca' => $gioLamTangCa,
                    'luong_co_ban' => $luongCoBan,
                    'luong_tang_ca' => $luongTangCa,
                    'hoa_hong' => [
                        'hd_tp' => $hoaHongTp,
                        'hd_sddv' => $hoaHongSddv,
                    ],
                    'san_xuat' => $sanXuat,
                ];
            }
        }

        $donGiaPhuCapThuBayChuNhat = $nhanVien->getLuongValue('phu_cap_thu_bay_va_chu_nhat');
        $phuCapThuBayChuNhat = round($soNgayLamThuBayChuNhat * $donGiaPhuCapThuBayChuNhat, 2);

        if ($loaiNhanVien === 'full_time') {
            $today = Carbon::now(self::TIMEZONE)->startOfDay();
            $chuyenCanDenNgay = $end->copy()->startOfDay();
            if ($chuyenCanDenNgay->gt($today)) {
                $chuyenCanDenNgay = $today;
            }

            $soNgayNghi = $start->gt($chuyenCanDenNgay)
                ? 0
                : $this->demSoNgayNghiFullTime($start, $chuyenCanDenNgay, $records, $holidayDates);
            $thuongChuyenCan = $this->tinhThuongChuyenCanFullTime($nhanVien, $soNgayNghi);
        } else {
            $soNgayNghi = 0;
            $thuongChuyenCan = $nhanVien->getLuongValue('thuong_chuyen_can');
        }

        $tongPhuCap = array_sum($phuCap);
        $tongHoaHong = $tong['hoa_hong_hd_tp'] + $tong['hoa_hong_hd_sddv'];
        $tongSanXuat = $tong['san_xuat_make']
            + $tong['san_xuat_chup']
            + $tong['san_xuat_quay_phim']
            + $tong['san_xuat_edit'];

        $tongA = $luongCung + $luongMem + $tongPhuCap;
        $tongB = $tong['tong_luong_theo_gio']
            + $tong['tong_tang_ca']
            + $tongHoaHong
            + $tongSanXuat
            + $phuCapThuBayChuNhat
            + $thuongChuyenCan;
        $tongC = $tong['tien_phat_di_muon']
            + $tong['tien_phat_ve_som']
            + $tong['phat_phat_sinh'];
        $thucNhan = round($tongA + $tongB - $tongC, 2);

        $result = [
            'thang' => $thang,
            'tu_ngay' => $tuNgay,
            'den_ngay' => $denNgay,
            'nhan_vien' => [
                'user_id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'loai_nhan_vien' => $nhanVien->loai_nhan_vien,
                'luong_co_dinh' => [
                    'luong_cung' => $luongCung,
                    'luong_mem' => $luongMem,
                ],
                'phu_cap' => $phuCap,
            ],
            'tong_ket' => [
                'gio_lam_co_ban' => round($tong['gio_lam_co_ban'], 2),
                'gio_lam_tang_ca' => round($tong['gio_lam_tang_ca'], 2),
                'tong_luong_theo_gio' => round($tong['tong_luong_theo_gio'], 2),
                'tong_tang_ca' => round($tong['tong_tang_ca'], 2),
                'hoa_hong_hd_tp' => round($tong['hoa_hong_hd_tp'], 2),
                'hoa_hong_hd_sddv' => round($tong['hoa_hong_hd_sddv'], 2),
                'san_xuat_make' => round($tong['san_xuat_make'], 2),
                'san_xuat_chup' => round($tong['san_xuat_chup'], 2),
                'san_xuat_quay_phim' => round($tong['san_xuat_quay_phim'], 2),
                'san_xuat_edit' => round($tong['san_xuat_edit'], 2),
                'phu_cap_thu_bay_va_chu_nhat' => round($phuCapThuBayChuNhat, 2),
                'so_ngay_lam_thu_bay_chu_nhat' => $soNgayLamThuBayChuNhat,
                'so_ngay_nghi' => $soNgayNghi,
                'thuong_chuyen_can' => round($thuongChuyenCan, 2),
                'tong_hoa_hong' => round($tongHoaHong, 2),
                'tong_san_xuat' => round($tongSanXuat, 2),
                'tong_phu_cap' => round($tongPhuCap, 2),
                'tien_phat_di_muon' => round($tong['tien_phat_di_muon'], 2),
                'tien_phat_ve_som' => round($tong['tien_phat_ve_som'], 2),
                'phat_phat_sinh' => round($tong['phat_phat_sinh'], 2),
                'tong_a' => round($tongA, 2),
                'tong_b' => round($tongB, 2),
                'tong_c' => round($tongC, 2),
                'tong_khau_tru' => round($tongC, 2),
                'thuc_nhan' => $thucNhan,
            ],
        ];

        if ($includeDays) {
            $result['days'] = $days;
        }

        return $result;
    }

    /**
     * @return array<string, true>
     */
    private function activeHolidayDates(string $tuNgay, string $denNgay): array
    {
        $rows = CauHinhNgayNghi::query()
            ->where('trang_thai', 'active')
            ->whereDate('ngay_ket_thuc', '>=', $tuNgay)
            ->whereDate('ngay_bat_dau', '<=', $denNgay)
            ->get(['ngay_bat_dau', 'ngay_ket_thuc']);

        $map = [];
        foreach ($rows as $row) {
            $start = Carbon::parse($row->ngay_bat_dau, self::TIMEZONE)->startOfDay();
            $end = Carbon::parse($row->ngay_ket_thuc, self::TIMEZONE)->startOfDay();
            for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
                $key = $date->toDateString();
                if ($key >= $tuNgay && $key <= $denNgay) {
                    $map[$key] = true;
                }
            }
        }

        return $map;
    }

    /**
     * @param  array<string, true>  $holidayDates
     */
    private function isNgayKhongTinhNghi(Carbon $date, array $holidayDates): bool
    {
        if ($date->isWeekend()) {
            return true;
        }

        return isset($holidayDates[$date->toDateString()]);
    }

    /**
     * Đếm ngày nghỉ của full_time trong khoảng [start, end]:
     * ngày trong tuần (không T7/CN, không ngày lễ active) không có bản ghi điểm danh.
     *
     * @param  Collection<string, DiemDanh>  $records
     * @param  array<string, true>  $holidayDates
     */
    private function demSoNgayNghiFullTime(Carbon $start, Carbon $end, Collection $records, array $holidayDates): int
    {
        $count = 0;
        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            if ($this->isNgayKhongTinhNghi($date, $holidayDates)) {
                continue;
            }

            if (! $records->has($date->toDateString())) {
                $count++;
            }
        }

        return $count;
    }

    private function tinhThuongChuyenCanFullTime(mixed $nhanVien, int $soNgayNghi): float
    {
        if ($soNgayNghi > 3) {
            return 0.0;
        }

        $keys = [
            0 => 'chuyen_can_khong_nghi',
            1 => 'chuyen_can_nghi_1_ngay',
            2 => 'chuyen_can_nghi_2_ngay',
            3 => 'chuyen_can_nghi_3_ngay',
        ];

        return $nhanVien->getLuongValue($keys[$soNgayNghi]);
    }

    /**
     * Hoa hồng HĐ trang phục theo ngày thuê.
     * Công thức ngày = Σ (tong_tien × tỷ lệ % / 100).
     *
     * @return array<string, float>
     */
    private function hoaHongTrangPhucByDate(int $userId, string $tuNgay, string $denNgay, float $phanTram): array
    {
        if ($phanTram <= 0) {
            return [];
        }

        $tyLe = $phanTram / 100;

        $rows = HopDongChoThueTrangPhuc::query()
            ->where('nguoi_cho_thue', $userId)
            ->where('tong_tien', '>', 0)
            ->whereNotIn('trang_thai', ['moi_tao', 'nhap', 'da_huy'])
            ->whereDate('ngay_thue', '>=', $tuNgay)
            ->whereDate('ngay_thue', '<=', $denNgay)
            ->get(['id', 'ngay_thue', 'tong_tien']);

        $map = [];
        foreach ($rows as $row) {
            $dateKey = $row->ngay_thue?->format('Y-m-d');
            if (! $dateKey) {
                continue;
            }
            $amount = (float) $row->tong_tien * $tyLe;
            $map[$dateKey] = round(($map[$dateKey] ?? 0) + $amount, 2);
        }

        return $map;
    }

    /**
     * Hoa hồng HĐ SDDV theo ngày tạo.
     * HĐ có nguoi_tao_id = user hoặc user nằm trong nguoi_tham_gia_ids,
     * tong_tien > 0, bỏ moi_tao/nhap/da_huy.
     * Công thức ngày = Σ (tong_tien × tỷ lệ % / 100).
     *
     * @return array<string, float>
     */
    private function hoaHongSddvByDate(int $userId, string $tuNgay, string $denNgay, float $phanTram): array
    {
        if ($phanTram <= 0) {
            return [];
        }

        $tyLe = $phanTram / 100;

        $rows = HopDongSuDungDichVu::query()
            ->where(function ($q) use ($userId) {
                $q->where('nguoi_tao_id', $userId)
                    ->orWhereJsonContains('nguoi_tham_gia_ids', $userId)
                    ->orWhereJsonContains('nguoi_tham_gia_ids', (string) $userId);
            })
            ->where('tong_tien', '>', 0)
            ->whereNotIn('trang_thai', ['moi_tao', 'nhap', 'da_huy'])
            ->whereDate('created_at', '>=', $tuNgay)
            ->whereDate('created_at', '<=', $denNgay)
            ->get(['id', 'created_at', 'tong_tien']);

        $map = [];
        foreach ($rows as $row) {
            $dateKey = $row->created_at
                ? Carbon::parse($row->created_at)->timezone(self::TIMEZONE)->toDateString()
                : null;
            if (! $dateKey) {
                continue;
            }
            $amount = (float) $row->tong_tien * $tyLe;
            $map[$dateKey] = round(($map[$dateKey] ?? 0) + $amount, 2);
        }

        return $map;
    }

    /**
     * Sản xuất (make/chụp/quay/edit) theo ngày chụp từ điều phối HĐ SDDV.
     *
     * @return array<string, array{make: float, chup: float, quay_phim: float, edit: float, so_job: array{make: int, chup: int, quay_phim: int, edit: int}}>
     */
    private function sanXuatByDate(int $userId, string $tuNgay, string $denNgay, mixed $nhanVien): array
    {
        $contracts = HopDongSuDungDichVu::query()
            ->with(['combos.combo:id,so_diem_chup'])
            ->whereNotIn('trang_thai', ['moi_tao', 'nhap', 'da_huy'])
            ->whereNotNull('thong_tin_dieu_phoi')
            ->where(function ($q) use ($userId) {
                foreach (array_keys(self::STAFF_ROLE_KEYS) as $key) {
                    for ($i = 0; $i < 6; $i++) {
                        $path = '$.danh_sach_buoi_chup['.$i.'].'.$key.'.gia_tri';
                        $q->orWhere(function ($inner) use ($path, $userId) {
                            $inner->whereRaw(
                                "JSON_CONTAINS(thong_tin_dieu_phoi, ?, '{$path}')",
                                [json_encode($userId)]
                            )->orWhereRaw(
                                "JSON_CONTAINS(thong_tin_dieu_phoi, ?, '{$path}')",
                                [json_encode((string) $userId)]
                            );
                        });
                    }
                }
            })
            ->get(['id', 'thong_tin_dieu_phoi']);

        $rates = [];
        foreach (['chup', 'make', 'quay_phim'] as $role) {
            $rates[$role] = [
                1 => $nhanVien->getLuongTheoDichVu($role, 1),
                2 => $nhanVien->getLuongTheoDichVu($role, 2),
                3 => $nhanVien->getLuongTheoDichVu($role, 3),
            ];
        }

        $map = [];
        foreach ($contracts as $hd) {
            $sessions = HopDongSuDungDichVu::normalizeDieuPhoiSessions($hd->thong_tin_dieu_phoi);
            $diem = $this->resolveSoDiemChup($hd->combos);

            foreach ($sessions as $session) {
                $dateKey = substr((string) HopDongSuDungDichVu::dieuPhoiGiaTri($session, 'ngay_chup'), 0, 10);
                if ($dateKey === '' || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateKey)) {
                    continue;
                }
                if ($dateKey < $tuNgay || $dateKey > $denNgay) {
                    continue;
                }

                $roles = $this->rolesOfUserInDieuPhoi($session, $userId);
                if ($roles === []) {
                    continue;
                }

                if (! isset($map[$dateKey])) {
                    $map[$dateKey] = $this->emptySanXuat();
                }

                foreach ($roles as $role) {
                    $map[$dateKey]['so_job'][$role] = (int) ($map[$dateKey]['so_job'][$role] ?? 0) + 1;

                    if (isset($rates[$role])) {
                        $map[$dateKey][$role] = round(
                            $map[$dateKey][$role] + $this->tienTheoDiem($rates[$role], $diem),
                            2
                        );
                    }
                }
            }
        }

        return $map;
    }

    /**
     * @param  Collection<int, mixed>  $combos
     */
    private function resolveSoDiemChup(Collection $combos): int
    {
        $max = 0;
        foreach ($combos as $item) {
            $max = max($max, (int) ($item->combo?->so_diem_chup ?? 0));
        }

        return $max;
    }

    /**
     * @param  array<string, mixed>  $dieuPhoi
     * @return list<string>
     */
    private function rolesOfUserInDieuPhoi(array $dieuPhoi, int $userId): array
    {
        $roles = [];
        foreach (self::STAFF_ROLE_KEYS as $field => $role) {
            $raw = $dieuPhoi[$field]['gia_tri'] ?? [];
            if (! is_array($raw)) {
                continue;
            }
            foreach ($raw as $value) {
                if ((int) $value === $userId) {
                    $roles[] = $role;
                    break;
                }
            }
        }

        return $roles;
    }

    /**
     * @param  array<int, float>  $rates
     */
    private function tienTheoDiem(array $rates, int $diem): float
    {
        if ($diem <= 0) {
            return 0.0;
        }

        $level = min(3, $diem);

        return round((float) ($rates[$level] ?? 0), 2);
    }

    /**
     * @return array{make: float, chup: float, quay_phim: float, edit: float, so_job: array{make: int, chup: int, quay_phim: int, edit: int}}
     */
    private function emptySanXuat(): array
    {
        return [
            'make' => 0.0,
            'chup' => 0.0,
            'quay_phim' => 0.0,
            'edit' => 0.0,
            'so_job' => [
                'make' => 0,
                'chup' => 0,
                'quay_phim' => 0,
                'edit' => 0,
            ],
        ];
    }
}
