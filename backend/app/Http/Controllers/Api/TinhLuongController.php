<?php

namespace App\Http\Controllers\Api;

use App\Models\CauHinhJson;
use App\Models\CauHinhNgayNghi;
use App\Models\ChotLuongThang;
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
     * Bảng lương chi tiết theo ngày trong tháng của một nhân viên (theo user_id).
     *
     * Query: user_id (required), thang (YYYY-MM, required)
     */
    public function chiTietTheoNgayNhanVien(Request $request): JsonResponse
    {
        return $this->handleApi(function () use ($request) {
            $validated = $request->validate([
                'user_id' => ['required', 'integer', 'min:1'],
                'thang' => ['required', 'string', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
            ]);

            $user = User::query()
                ->with(['nhanVien:id,user_id,loai_nhan_vien,cong_chuan,luong_thuong_phu_cap'])
                ->select(['id', 'name', 'email'])
                ->findOrFail((int) $validated['user_id']);

            $nhanVien = $user->nhanVien;
            if (! $nhanVien) {
                throw ValidationException::withMessages([
                    'nhan_vien' => ['Tài khoản chưa có hồ sơ nhân sự.'],
                ]);
            }

            $payload = $this->buildBangLuongThang($user, $nhanVien, $validated['thang'], includeDays: true);

            return response()->json($payload);
        }, 'lấy bảng lương chi tiết theo ngày của nhân viên');
    }

    /**
     * Lương tổng hợp theo tháng — danh sách nhân viên (phân trang).
     * Tháng hiện tại / tháng trước nếu đã chốt → trả snapshot du_lieu_chot.
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

            $thang = $validated['thang'];
            $perPage = $validated['per_page'] ?? 10;
            $keyword = trim((string) ($validated['keyword'] ?? ''));
            $page = max(1, (int) ($validated['page'] ?? $request->input('page', 1)));

            // Ưu tiên snapshot đã chốt (mọi tháng).
            $lockedPayload = $this->tongHopTuDuLieuChot($thang, $keyword, $page, $perPage);
            if ($lockedPayload !== null) {
                return response()->json($lockedPayload);
            }

            // Tháng cách đây ≥ 2 tháng mà chưa chốt → không có dữ liệu lịch sử.
            if ($this->isThangCuHonThangTruoc($thang)) {
                return response()->json([
                    'thang' => $thang,
                    'da_chot' => false,
                    'nguon' => 'khong_co_chot',
                    'message' => 'Không có dữ liệu chốt lương cho tháng này.',
                    'data' => [],
                    'current_page' => 1,
                    'last_page' => 1,
                    'per_page' => $perPage,
                    'total' => 0,
                ]);
            }

            $query = User::query()
                ->with([$this->nhanVienTongHopWith()])
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

            $paginator = $query->paginate($perPage, ['*'], 'page', $page);

            $items = collect($paginator->items())
                ->map(fn (User $user) => $this->mapTongHopRow($user, $thang))
                ->values();

            return response()->json([
                'thang' => $thang,
                'da_chot' => false,
                'nguon' => 'tinh_toan',
                'data' => $items,
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ]);
        }, 'lấy lương tổng hợp theo tháng');
    }

    /**
     * Trạng thái chốt lương theo tháng + quyền chốt theo kỳ cấu hình.
     *
     * Query: thang (YYYY-MM, required)
     */
    public function trangThaiChot(Request $request): JsonResponse
    {
        return $this->handleApi(function () use ($request) {
            $validated = $request->validate([
                'thang' => ['required', 'string', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
            ]);

            $thang = $validated['thang'];
            [$nam, $thangSo] = array_map('intval', explode('-', $thang));
            $kyChot = $this->kyChotLuongConfig();
            $kyWindow = $this->kyChotLuongWindow($thang, $kyChot['ngay_bat_dau'], $kyChot['ngay_ket_thuc']);
            $trongKy = $this->isTrongKyChotLuong($kyWindow['tu_ngay'], $kyWindow['den_ngay']);

            $record = ChotLuongThang::query()
                ->where('thang', $thangSo)
                ->where('nam', $nam)
                ->first();

            $daChot = $record?->trang_thai === ChotLuongThang::TRANG_THAI_DA_CHOT;

            return response()->json([
                'thang' => $thang,
                'trang_thai' => $record?->trang_thai ?? ChotLuongThang::TRANG_THAI_CHUA_CHOT,
                'da_chot' => $daChot,
                'co_the_chot' => $trongKy && ! $daChot,
                'co_the_huy_chot' => $trongKy && $daChot,
                'trong_ky_chot' => $trongKy,
                'ky_chot_luong' => array_merge($kyChot, $kyWindow),
                'nguoi_chot_id' => $record?->nguoi_chot_id,
                'updated_at' => $record?->updated_at?->toIso8601String(),
            ]);
        }, 'lấy trạng thái chốt lương tháng');
    }

    /**
     * Chốt lương tháng — lưu snapshot tổng hợp toàn bộ nhân viên vào chot_luong_thang.
     *
     * Body: thang (YYYY-MM, required)
     */
    public function chotThang(Request $request): JsonResponse
    {
        return $this->handleApi(function () use ($request) {
            $validated = $request->validate([
                'thang' => ['required', 'string', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
            ]);

            $thang = $validated['thang'];
            [$nam, $thangSo] = array_map('intval', explode('-', $thang));
            $kyChot = $this->kyChotLuongConfig();
            $kyWindow = $this->kyChotLuongWindow($thang, $kyChot['ngay_bat_dau'], $kyChot['ngay_ket_thuc']);

            if (! $this->isTrongKyChotLuong($kyWindow['tu_ngay'], $kyWindow['den_ngay'])) {
                throw ValidationException::withMessages([
                    'thang' => ['Hiện không nằm trong kỳ chốt lương của tháng này. Không thể chốt lương.'],
                ]);
            }

            $existing = ChotLuongThang::query()
                ->where('thang', $thangSo)
                ->where('nam', $nam)
                ->where('trang_thai', ChotLuongThang::TRANG_THAI_DA_CHOT)
                ->first();

            if ($existing) {
                throw ValidationException::withMessages([
                    'thang' => ['Tháng này đã được chốt lương.'],
                ]);
            }

            $users = User::query()
                ->with([$this->nhanVienTongHopWith()])
                ->select(['id', 'name', 'email', 'phone', 'status'])
                ->where('status', 'active')
                ->whereHas('nhanVien')
                ->orderBy('name')
                ->get();

            $items = $users
                ->map(fn (User $user) => $this->mapTongHopRow($user, $thang, forChot: true))
                ->values()
                ->all();

            $record = ChotLuongThang::query()->updateOrCreate(
                [
                    'thang' => $thangSo,
                    'nam' => $nam,
                ],
                [
                    'nguoi_chot_id' => (int) $request->user()->id,
                    'du_lieu_chot' => [
                        'thang' => $thang,
                        'tong_nhan_vien' => count($items),
                        'items' => $items,
                        'chot_luc' => Carbon::now(self::TIMEZONE)->toIso8601String(),
                    ],
                    'trang_thai' => ChotLuongThang::TRANG_THAI_DA_CHOT,
                ]
            );

            return response()->json([
                'message' => 'Đã chốt lương tháng '.$thang.'.',
                'thang' => $thang,
                'trang_thai' => $record->trang_thai,
                'da_chot' => true,
                'co_the_chot' => false,
                'co_the_huy_chot' => true,
                'trong_ky_chot' => true,
                'ky_chot_luong' => array_merge($kyChot, $kyWindow),
                'nguoi_chot_id' => $record->nguoi_chot_id,
                'tong_nhan_vien' => count($items),
                'updated_at' => $record->updated_at?->toIso8601String(),
            ], 201);
        }, 'chốt lương tháng');
    }

    /**
     * Huỷ chốt lương tháng — chỉ cho phép trong kỳ chốt.
     *
     * Body: thang (YYYY-MM, required)
     */
    public function huyChotThang(Request $request): JsonResponse
    {
        return $this->handleApi(function () use ($request) {
            $validated = $request->validate([
                'thang' => ['required', 'string', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
            ]);

            $thang = $validated['thang'];
            [$nam, $thangSo] = array_map('intval', explode('-', $thang));
            $kyChot = $this->kyChotLuongConfig();
            $kyWindow = $this->kyChotLuongWindow($thang, $kyChot['ngay_bat_dau'], $kyChot['ngay_ket_thuc']);
            $trongKy = $this->isTrongKyChotLuong($kyWindow['tu_ngay'], $kyWindow['den_ngay']);

            if (! $trongKy) {
                throw ValidationException::withMessages([
                    'thang' => ['Hiện không nằm trong kỳ chốt lương của tháng này. Không thể huỷ chốt.'],
                ]);
            }

            $record = ChotLuongThang::query()
                ->where('thang', $thangSo)
                ->where('nam', $nam)
                ->where('trang_thai', ChotLuongThang::TRANG_THAI_DA_CHOT)
                ->first();

            if (! $record) {
                throw ValidationException::withMessages([
                    'thang' => ['Tháng này chưa được chốt lương.'],
                ]);
            }

            $record->update([
                'trang_thai' => ChotLuongThang::TRANG_THAI_CHUA_CHOT,
                'du_lieu_chot' => null,
                'nguoi_chot_id' => null,
            ]);

            return response()->json([
                'message' => 'Đã huỷ chốt lương tháng '.$thang.'.',
                'thang' => $thang,
                'trang_thai' => ChotLuongThang::TRANG_THAI_CHUA_CHOT,
                'da_chot' => false,
                'co_the_chot' => true,
                'co_the_huy_chot' => false,
                'trong_ky_chot' => true,
                'ky_chot_luong' => array_merge($kyChot, $kyWindow),
                'nguoi_chot_id' => null,
                'updated_at' => $record->fresh()?->updated_at?->toIso8601String(),
            ]);
        }, 'huỷ chốt lương tháng');
    }

    /**
     * Đánh dấu đã chuyển lương cho một nhân viên trong snapshot chốt tháng.
     *
     * Body: thang (YYYY-MM), user_id
     */
    public function chuyenLuong(Request $request): JsonResponse
    {
        return $this->handleApi(function () use ($request) {
            $validated = $request->validate([
                'thang' => ['required', 'string', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
                'user_id' => ['required', 'integer', 'min:1'],
            ]);

            $thang = $validated['thang'];
            $userId = (int) $validated['user_id'];
            [$nam, $thangSo] = array_map('intval', explode('-', $thang));

            $record = ChotLuongThang::query()
                ->where('thang', $thangSo)
                ->where('nam', $nam)
                ->where('trang_thai', ChotLuongThang::TRANG_THAI_DA_CHOT)
                ->first();

            if (! $record) {
                throw ValidationException::withMessages([
                    'thang' => ['Tháng này chưa được chốt lương. Không thể chuyển lương.'],
                ]);
            }

            $duLieu = is_array($record->du_lieu_chot) ? $record->du_lieu_chot : [];
            $items = is_array($duLieu['items'] ?? null) ? $duLieu['items'] : [];
            $foundIndex = null;

            foreach ($items as $index => $row) {
                if (! is_array($row)) {
                    continue;
                }
                if ((int) ($row['user_id'] ?? 0) === $userId) {
                    $foundIndex = $index;
                    break;
                }
            }

            if ($foundIndex === null) {
                throw ValidationException::withMessages([
                    'user_id' => ['Không tìm thấy nhân viên trong dữ liệu chốt lương tháng này.'],
                ]);
            }

            if (($items[$foundIndex]['trang_thai_thanh_toan'] ?? null) === ChotLuongThang::THANH_TOAN_DA) {
                throw ValidationException::withMessages([
                    'user_id' => ['Nhân viên này đã được chuyển lương.'],
                ]);
            }

            $items[$foundIndex]['trang_thai_thanh_toan'] = ChotLuongThang::THANH_TOAN_DA;
            $items[$foundIndex]['thanh_toan_luc'] = Carbon::now(self::TIMEZONE)->toIso8601String();
            $duLieu['items'] = array_values($items);

            $record->update([
                'du_lieu_chot' => $duLieu,
            ]);

            return response()->json([
                'message' => 'Đã cập nhật trạng thái thanh toán lương.',
                'thang' => $thang,
                'user_id' => $userId,
                'trang_thai_thanh_toan' => ChotLuongThang::THANH_TOAN_DA,
                'thanh_toan_luc' => $items[$foundIndex]['thanh_toan_luc'],
            ]);
        }, 'chuyển lương nhân viên');
    }

    /**
     * Trả snapshot đã chốt nếu tháng có bản ghi da_chot trong chot_luong_thang.
     *
     * @return array<string, mixed>|null
     */
    private function tongHopTuDuLieuChot(string $thang, string $keyword, int $page, int $perPage): ?array
    {
        [$nam, $thangSo] = array_map('intval', explode('-', $thang));
        $record = ChotLuongThang::query()
            ->where('thang', $thangSo)
            ->where('nam', $nam)
            ->where('trang_thai', ChotLuongThang::TRANG_THAI_DA_CHOT)
            ->first();

        if (! $record) {
            return null;
        }

        $duLieu = is_array($record->du_lieu_chot) ? $record->du_lieu_chot : [];
        $items = collect(is_array($duLieu['items'] ?? null) ? $duLieu['items'] : [])
            ->filter(fn ($row) => is_array($row))
            ->values();

        if ($keyword !== '') {
            $needle = mb_strtolower($keyword);
            $items = $items->filter(function (array $row) use ($needle) {
                $haystack = mb_strtolower(implode(' ', [
                    (string) ($row['name'] ?? ''),
                    (string) ($row['email'] ?? ''),
                    (string) ($row['phone'] ?? ''),
                ]));

                return str_contains($haystack, $needle);
            })->values();
        }

        $total = $items->count();
        $lastPage = max(1, (int) ceil($total / max(1, $perPage)));
        $page = min($page, $lastPage);
        $pageItems = $items->slice(($page - 1) * $perPage, $perPage)->values();

        return [
            'thang' => $thang,
            'da_chot' => true,
            'nguon' => 'chot',
            'data' => $pageItems,
            'current_page' => $page,
            'last_page' => $lastPage,
            'per_page' => $perPage,
            'total' => $total,
        ];
    }

    private function isThangHienTaiHoacThangTruoc(string $thang): bool
    {
        $now = Carbon::now(self::TIMEZONE)->startOfMonth();
        $target = Carbon::createFromFormat('Y-m', $thang, self::TIMEZONE)->startOfMonth();

        return $target->equalTo($now) || $target->equalTo($now->copy()->subMonth());
    }

    /** Tháng cách hiện tại ≥ 2 tháng (cũ hơn tháng liền trước). */
    private function isThangCuHonThangTruoc(string $thang): bool
    {
        return ! $this->isThangHienTaiHoacThangTruoc($thang)
            && Carbon::createFromFormat('Y-m', $thang, self::TIMEZONE)->startOfMonth()
                ->lt(Carbon::now(self::TIMEZONE)->startOfMonth());
    }

    /**
     * Columns eager-load cho mapTongHopRow (kèm thông tin nhận lương).
     */
    private function nhanVienTongHopWith(): string
    {
        return 'nhanVien:id,user_id,loai_nhan_vien,cong_chuan,luong_thuong_phu_cap,ngan_hang,chi_nhanh,so_tai_khoan,chu_tai_khoan';
    }

    /**
     * @return array<string, mixed>
     */
    private function mapTongHopRow(User $user, string $thang, bool $forChot = false): array
    {
        $nhanVien = $user->nhanVien;
        $payload = $this->buildBangLuongThang($user, $nhanVien, $thang, includeDays: false);
        $nv = $payload['nhan_vien'];
        $tong = $payload['tong_ket'];
        $phuCap = $nv['phu_cap'];

        $row = [
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'loai_nhan_vien' => $nhanVien?->loai_nhan_vien,
            'a' => [
                'luong_cung' => $nv['luong_co_dinh']['luong_cung'],
                'luong_mem' => $nv['luong_co_dinh']['luong_mem'],
                'phu_cap' => $phuCap['phu_cap'],
                'phu_cap_xang' => $phuCap['phu_cap_xang'],
                'phu_cap_an_trua' => $phuCap['phu_cap_an_trua'],
                'phu_cap_dien_thoai' => $phuCap['phu_cap_dien_thoai'],
                'phu_cap_nha_o' => $phuCap['phu_cap_nha_o'],
                'tong' => $tong['tong_a'],
            ],
            'b' => [
                'tong_luong_theo_gio' => $tong['tong_luong_theo_gio'],
                'tong_tang_ca' => $tong['tong_tang_ca'],
                'hoa_hong_hd_tp' => $tong['hoa_hong_hd_tp'],
                'hoa_hong_hd_sddv' => $tong['hoa_hong_hd_sddv'],
                'san_xuat_make' => $tong['san_xuat_make'],
                'san_xuat_chup' => $tong['san_xuat_chup'],
                'san_xuat_quay_phim' => $tong['san_xuat_quay_phim'],
                'san_xuat_edit' => $tong['san_xuat_edit'],
                'phu_cap_thu_bay_va_chu_nhat' => $tong['phu_cap_thu_bay_va_chu_nhat'],
                'thuong_chuyen_can' => $tong['thuong_chuyen_can'],
                'tong' => $tong['tong_b'],
            ],
            'c' => [
                'tien_phat_di_muon' => $tong['tien_phat_di_muon'],
                'tien_phat_ve_som' => $tong['tien_phat_ve_som'],
                'phat_phat_sinh' => $tong['phat_phat_sinh'],
                'tong' => $tong['tong_c'],
            ],
            'thuc_nhan' => $tong['thuc_nhan'],
        ];

        if ($forChot) {
            $row['trang_thai_thanh_toan'] = ChotLuongThang::THANH_TOAN_CHUA;
            $row['thong_tin_nguoi_nhan'] = [
                'ngan_hang' => $nhanVien?->ngan_hang,
                'chi_nhanh' => $nhanVien?->chi_nhanh,
                'so_tai_khoan' => $nhanVien?->so_tai_khoan,
                'chu_tai_khoan' => $nhanVien?->chu_tai_khoan,
            ];
        }

        return $row;
    }

    /**
     * @return array{ngay_bat_dau: int, ngay_ket_thuc: int}
     */
    private function kyChotLuongConfig(): array
    {
        $row = CauHinhJson::query()->first();
        $all = is_array($row?->thong_tin_cau_hinh) ? $row->thong_tin_cau_hinh : [];
        $group = is_array($all['luong_va_hoa_hong'] ?? null) ? $all['luong_va_hoa_hong'] : [];
        $ky = is_array($group['ky_chot_luong'] ?? null) ? $group['ky_chot_luong'] : [];

        $batDau = (int) ($ky['ngay_bat_dau'] ?? 26);
        $ketThuc = (int) ($ky['ngay_ket_thuc'] ?? 25);

        return [
            'ngay_bat_dau' => max(1, min(31, $batDau)),
            'ngay_ket_thuc' => max(1, min(31, $ketThuc)),
        ];
    }

    /**
     * Khoảng ngày được phép chốt cho một tháng lương.
     * - Qua tháng (vd 26→25): từ ngày bắt đầu của tháng lương đến ngày kết thúc tháng kế tiếp.
     * - Trong cùng tháng (vd 1→5): từ ngày bắt đầu đến ngày kết thúc của tháng kế sau tháng lương.
     *
     * @return array{tu_ngay: string, den_ngay: string}
     */
    private function kyChotLuongWindow(string $thang, int $ngayBatDau, int $ngayKetThuc): array
    {
        $month = Carbon::createFromFormat('Y-m', $thang, self::TIMEZONE)->startOfMonth();

        if ($ngayBatDau <= $ngayKetThuc) {
            $next = $month->copy()->addMonth();
            $tuNgay = $this->clampDayInMonth($next, $ngayBatDau)->startOfDay();
            $denNgay = $this->clampDayInMonth($next, $ngayKetThuc)->endOfDay();
        } else {
            $tuNgay = $this->clampDayInMonth($month, $ngayBatDau)->startOfDay();
            $denNgay = $this->clampDayInMonth($month->copy()->addMonth(), $ngayKetThuc)->endOfDay();
        }

        return [
            'tu_ngay' => $tuNgay->toDateString(),
            'den_ngay' => $denNgay->toDateString(),
        ];
    }

    private function clampDayInMonth(Carbon $month, int $day): Carbon
    {
        $maxDay = $month->daysInMonth;

        return $month->copy()->day(min(max(1, $day), $maxDay));
    }

    private function isTrongKyChotLuong(string $tuNgay, string $denNgay): bool
    {
        $now = Carbon::now(self::TIMEZONE);

        return $now->betweenIncluded(
            Carbon::parse($tuNgay, self::TIMEZONE)->startOfDay(),
            Carbon::parse($denNgay, self::TIMEZONE)->endOfDay(),
        );
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

        $hoaHongTpByDate = $this->hoaHongTrangPhucByDate(
            $userId,
            $tuNgay,
            $denNgay,
            $nhanVien->getLuongValue('hoa_hong_hop_dong_trang_phuc'),
            withChiTiet: $includeDays,
        );
        $hoaHongSddvByDate = $this->hoaHongSddvByDate(
            $userId,
            $tuNgay,
            $denNgay,
            $nhanVien->getLuongValue('hoa_hong_hop_dong_sddv'),
            withChiTiet: $includeDays,
        );
        $sanXuatByDate = $this->sanXuatByDate($userId, $tuNgay, $denNgay, $nhanVien, withChiTiet: $includeDays);

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
            $hoaHongTpEntry = $hoaHongTpByDate[$dateKey] ?? null;
            $hoaHongSddvEntry = $hoaHongSddvByDate[$dateKey] ?? null;
            $hoaHongTp = round((float) (is_array($hoaHongTpEntry) ? ($hoaHongTpEntry['tong'] ?? 0) : 0), 2);
            $hoaHongSddv = round((float) (is_array($hoaHongSddvEntry) ? ($hoaHongSddvEntry['tong'] ?? 0) : 0), 2);
            $sanXuat = $sanXuatByDate[$dateKey] ?? $this->emptySanXuat($includeDays);

            $gioLamCoBan = round((float) ($record?->gio_lam_co_ban ?? 0), 2);
            $gioLamTangCa = round((float) ($record?->gio_lam_tang_ca ?? 0), 2);
            $luongCoBan = round((float) ($record?->luong_co_ban ?? 0), 2);
            $luongTangCa = round((float) ($record?->luong_tang_ca ?? 0), 2);
            $tienPhatDiMuon = round((float) ($record?->tien_phat_di_muon ?? 0), 2);
            $tienPhatVeSom = round((float) ($record?->tien_phat_ve_som ?? 0), 2);
            $thoiGianDiMuon = (int) ($record?->thoi_gian_di_muon ?? 0);
            $thoiGianVeSom = (int) ($record?->thoi_gian_ve_som ?? 0);

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
                    'is_holiday' => isset($holidayDates[$dateKey]),
                    'co_diem_danh' => $record !== null,
                    'gio_vao' => $record?->gio_vao,
                    'gio_ra' => $record?->gio_ra,
                    'gio_lam_co_ban' => $gioLamCoBan,
                    'gio_lam_tang_ca' => $gioLamTangCa,
                    'luong_co_ban' => $luongCoBan,
                    'luong_tang_ca' => $luongTangCa,
                    'thoi_gian_di_muon' => $thoiGianDiMuon,
                    'thoi_gian_ve_som' => $thoiGianVeSom,
                    'tien_phat_di_muon' => $tienPhatDiMuon,
                    'tien_phat_ve_som' => $tienPhatVeSom,
                    'hoa_hong' => [
                        'hd_tp' => $hoaHongTp,
                        'hd_sddv' => $hoaHongSddv,
                        'chi_tiet' => [
                            'hd_tp' => is_array($hoaHongTpEntry) ? ($hoaHongTpEntry['chi_tiet'] ?? []) : [],
                            'hd_sddv' => is_array($hoaHongSddvEntry) ? ($hoaHongSddvEntry['chi_tiet'] ?? []) : [],
                        ],
                    ],
                    'san_xuat' => $sanXuat,
                ];
            }
        }

        $donGiaPhuCapThuBayChuNhat = $nhanVien->getLuongValue('phu_cap_thu_bay_va_chu_nhat');
        $phuCapThuBayChuNhat = round($soNgayLamThuBayChuNhat * $donGiaPhuCapThuBayChuNhat, 2);

        $danhSachNgayNghi = [];
        if ($loaiNhanVien === 'full_time') {
            $today = Carbon::now(self::TIMEZONE)->startOfDay();
            $chuyenCanDenNgay = $end->copy()->startOfDay();
            if ($chuyenCanDenNgay->gt($today)) {
                $chuyenCanDenNgay = $today;
            }

            if (! $start->gt($chuyenCanDenNgay)) {
                $danhSachNgayNghi = $this->danhSachNgayNghiFullTime($start, $chuyenCanDenNgay, $records, $holidayDates);
            }
            $soNgayNghi = count($danhSachNgayNghi);
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
                'danh_sach_ngay_nghi' => $danhSachNgayNghi,
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
    /**
     * @param  Collection<string, DiemDanh>  $records
     * @param  array<string, true>  $holidayDates
     * @return list<string>
     */
    private function danhSachNgayNghiFullTime(Carbon $start, Carbon $end, Collection $records, array $holidayDates): array
    {
        $dates = [];
        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            if ($this->isNgayKhongTinhNghi($date, $holidayDates)) {
                continue;
            }

            if (! $records->has($date->toDateString())) {
                $dates[] = $date->toDateString();
            }
        }

        return $dates;
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
     * Hoa hồng HĐ trang phục theo ngày tạo (created_at).
     * HĐ có nguoi_cho_thue = user hoặc user nằm trong nguoi_tham_gia,
     * thanh_tien > 0, trang_thai khác moi_tao.
     * Công thức ngày = Σ (thanh_tien × tỷ lệ % / 100).
     *
     * @return array<string, array{tong: float, chi_tiet?: list<array<string, mixed>>}>
     */
    private function hoaHongTrangPhucByDate(int $userId, string $tuNgay, string $denNgay, float $phanTram, bool $withChiTiet = false): array
    {
        if ($phanTram <= 0) {
            return [];
        }

        $tyLe = $phanTram / 100;

        $rows = HopDongChoThueTrangPhuc::query()
            ->where(function ($q) use ($userId) {
                $q->where('nguoi_cho_thue', $userId)
                    ->orWhereJsonContains('nguoi_tham_gia', $userId)
                    ->orWhereJsonContains('nguoi_tham_gia', (string) $userId);
            })
            ->where('thanh_tien', '>', 0)
            ->where('trang_thai', '!=', 'moi_tao')
            ->whereDate('created_at', '>=', $tuNgay)
            ->whereDate('created_at', '<=', $denNgay)
            ->orderBy('created_at')
            ->get(['id', 'ma_hop_dong', 'ten_khach_hang', 'created_at', 'thanh_tien', 'nguoi_cho_thue', 'nguoi_tham_gia', 'trang_thai']);

        $map = [];
        foreach ($rows as $row) {
            $dateKey = $row->created_at
                ? Carbon::parse($row->created_at)->timezone(self::TIMEZONE)->toDateString()
                : null;
            if (! $dateKey) {
                continue;
            }

            $giaTri = (float) $row->thanh_tien;
            $amount = round($giaTri * $tyLe, 2);

            if (! isset($map[$dateKey])) {
                $map[$dateKey] = ['tong' => 0.0];
                if ($withChiTiet) {
                    $map[$dateKey]['chi_tiet'] = [];
                }
            }

            $map[$dateKey]['tong'] = round($map[$dateKey]['tong'] + $amount, 2);

            if (! $withChiTiet) {
                continue;
            }

            $map[$dateKey]['chi_tiet'][] = [
                'hop_dong_id' => (int) $row->id,
                'ma_hop_dong' => (string) ($row->ma_hop_dong ?? ''),
                'ten_khach_hang' => (string) ($row->ten_khach_hang ?? ''),
                'trang_thai' => (string) ($row->trang_thai ?? ''),
                'ngay_tao' => $dateKey,
                'vai_tro' => $this->resolveVaiTroHoaHongTp($row, $userId),
                'gia_tri_hop_dong' => $giaTri,
                'ty_le' => $phanTram,
                'hoa_hong' => $amount,
            ];
        }

        return $map;
    }

    /**
     * Hoa hồng HĐ SDDV theo ngày tạo.
     * HĐ có nguoi_tao_id = user hoặc user nằm trong nguoi_tham_gia_ids,
     * tong_tien_khach_phai_thanh_toan > 0, bỏ moi_tao/nhap/da_huy.
     * Công thức ngày = Σ (tong_tien_khach_phai_thanh_toan × tỷ lệ % / 100).
     *
     * @return array<string, array{tong: float, chi_tiet?: list<array<string, mixed>>}>
     */
    private function hoaHongSddvByDate(int $userId, string $tuNgay, string $denNgay, float $phanTram, bool $withChiTiet = false): array
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
            ->where('tong_tien_khach_phai_thanh_toan', '>', 0)
            ->whereNotIn('trang_thai', ['moi_tao', 'nhap', 'da_huy'])
            ->whereDate('created_at', '>=', $tuNgay)
            ->whereDate('created_at', '<=', $denNgay)
            ->orderBy('created_at')
            ->get(['id', 'ma_hop_dong', 'ten_khach_hang', 'created_at', 'tong_tien_khach_phai_thanh_toan', 'nguoi_tao_id', 'nguoi_tham_gia_ids', 'trang_thai']);

        $map = [];
        foreach ($rows as $row) {
            $dateKey = $row->created_at
                ? Carbon::parse($row->created_at)->timezone(self::TIMEZONE)->toDateString()
                : null;
            if (! $dateKey) {
                continue;
            }

            $giaTri = (float) $row->tong_tien_khach_phai_thanh_toan;
            $amount = round($giaTri * $tyLe, 2);

            if (! isset($map[$dateKey])) {
                $map[$dateKey] = ['tong' => 0.0];
                if ($withChiTiet) {
                    $map[$dateKey]['chi_tiet'] = [];
                }
            }

            $map[$dateKey]['tong'] = round($map[$dateKey]['tong'] + $amount, 2);

            if (! $withChiTiet) {
                continue;
            }

            $map[$dateKey]['chi_tiet'][] = [
                'hop_dong_id' => (int) $row->id,
                'ma_hop_dong' => (string) ($row->ma_hop_dong ?? ''),
                'ten_khach_hang' => (string) ($row->ten_khach_hang ?? ''),
                'trang_thai' => (string) ($row->trang_thai ?? ''),
                'ngay_tao' => $dateKey,
                'vai_tro' => $this->resolveVaiTroHoaHongSddv($row, $userId),
                'gia_tri_hop_dong' => $giaTri,
                'ty_le' => $phanTram,
                'hoa_hong' => $amount,
            ];
        }

        return $map;
    }

    private function resolveVaiTroHoaHongTp(mixed $row, int $userId): string
    {
        if ((int) ($row->nguoi_cho_thue ?? 0) === $userId) {
            return 'Người cho thuê';
        }

        return 'Người tham gia';
    }

    private function resolveVaiTroHoaHongSddv(mixed $row, int $userId): string
    {
        if ((int) ($row->nguoi_tao_id ?? 0) === $userId) {
            return 'Người tạo';
        }

        return 'Người tham gia';
    }

    /**
     * Sản xuất (make/chụp/quay/edit) theo ngày hoàn tất sản xuất từ HĐ SDDV.
     *
     * Nguồn: hop_dong_su_dung_dich_vu đã ghi thoi_gian_hoan_tat_san_xuat
     * (đã từng chuyển sang trang_thai_dieu_phoi = hoan_tat_san_xuat).
     * Ngày tính lương = DATE(thoi_gian_hoan_tat_san_xuat) theo Asia/Ho_Chi_Minh.
     *
     * Make / Chụp / Quay phim:
     * Mỗi buổi trong danh_sach_buoi_chup mà user được gán (tho_make / tho_chup /
     * quay_phim): lấy so_diem_chup + loai_quay_chup, map sang
     * nhan_vien.luong_thuong_phu_cap.luong_theo_dich_vu.items[ma_dich_vu]
     * → đơn giá role theo mức điểm 1/2/3.
     * Nhiều buổi trong 1 HĐ cộng dồn; nhiều HĐ trong 1 ngày cộng dồn.
     *
     * Edit:
     * User được gán tho_edit ở ít nhất một buổi của HĐ → tính 1 lần / HĐ.
     * Tổng ảnh = Σ (hop_dong_dong_sddv_combos.so_luong ×
     *   dich_vu_danh_sach_dich_nhom_dich_vu.so_anh_chinh_sua).
     * Thành tiền = tổng ảnh × luong_thuong_phu_cap.luong_chinh_sua_anh.
     * Nhiều HĐ trong 1 ngày → cộng dồn.
     *
     * Khi $withChiTiet = true, mỗi ngày kèm chi_tiet.{role}[] để hiển thị modal.
     *
     * @return array<string, array{make: float, chup: float, quay_phim: float, edit: float, so_job: array{make: int, chup: int, quay_phim: int, edit: int}, chi_tiet?: array<string, list<array<string, mixed>>>}>
     */
    private function sanXuatByDate(int $userId, string $tuNgay, string $denNgay, mixed $nhanVien, bool $withChiTiet = false): array
    {
        $hoanTatKey = HopDongSuDungDichVu::THOI_GIAN_HOAN_TAT_SAN_XUAT_KEY;
        $dateExpr = "LEFT(NULLIF(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(thong_tin_dieu_phoi, '$.{$hoanTatKey}')), 'null'), ''), 10)";

        $contracts = HopDongSuDungDichVu::query()
            ->with([
                'combos:id,ma_hop_dong_id,combo_id,so_luong',
                'combos.combo:id,ma_nhom,ten_nhom,so_anh_chinh_sua',
            ])
            ->whereNotIn('trang_thai', ['moi_tao', 'nhap', 'da_huy'])
            ->whereNotNull('thong_tin_dieu_phoi')
            ->whereRaw("{$dateExpr} IS NOT NULL")
            ->whereRaw("{$dateExpr} BETWEEN ? AND ?", [$tuNgay, $denNgay])
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
            ->get(['id', 'ma_hop_dong', 'ten_khach_hang', 'thong_tin_dieu_phoi']);

        $donGiaEdit = round((float) $nhanVien->getLuongValue('luong_chinh_sua_anh'), 2);

        $map = [];
        foreach ($contracts as $hd) {
            $envelope = is_array($hd->thong_tin_dieu_phoi) ? $hd->thong_tin_dieu_phoi : [];
            $dateKey = $this->resolveNgayHoanTatSanXuat($envelope);
            if ($dateKey === null || $dateKey < $tuNgay || $dateKey > $denNgay) {
                continue;
            }

            $sessions = HopDongSuDungDichVu::normalizeDieuPhoiSessions($envelope);
            $isThoEditHopDong = false;

            foreach ($sessions as $sessionIndex => $session) {
                $roles = $this->rolesOfUserInDieuPhoi($session, $userId);
                if ($roles === []) {
                    continue;
                }

                if (in_array('edit', $roles, true)) {
                    $isThoEditHopDong = true;
                }

                $diem = $this->resolveSoDiemChupFromSession($session);
                $loaiId = $this->resolveLoaiQuayChupId($session);
                $loaiTen = $this->resolveLoaiQuayChupTen($session);
                $tenBuoi = $this->resolveTenBuoi($session, $sessionIndex);
                $ngayChup = substr((string) HopDongSuDungDichVu::dieuPhoiGiaTri($session, 'ngay_chup'), 0, 10);
                if ($ngayChup === '' || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $ngayChup)) {
                    $ngayChup = null;
                }

                if (! isset($map[$dateKey])) {
                    $map[$dateKey] = $this->emptySanXuat($withChiTiet);
                }

                foreach ($roles as $role) {
                    if ($role === 'edit') {
                        continue;
                    }

                    $map[$dateKey]['so_job'][$role] = (int) ($map[$dateKey]['so_job'][$role] ?? 0) + 1;

                    $donGia = 0.0;
                    $thanhTien = 0.0;
                    if (in_array($role, ['make', 'chup', 'quay_phim'], true) && $diem > 0 && $loaiId !== null) {
                        $donGia = round((float) $nhanVien->getLuongTheoDichVu($role, $diem, $loaiId), 2);
                        $thanhTien = $donGia;
                        if ($thanhTien > 0) {
                            $map[$dateKey][$role] = round($map[$dateKey][$role] + $thanhTien, 2);
                        }
                    }

                    if (! $withChiTiet || ! in_array($role, ['make', 'chup', 'quay_phim'], true)) {
                        continue;
                    }

                    $map[$dateKey]['chi_tiet'][$role][] = [
                        'hop_dong_id' => (int) $hd->id,
                        'ma_hop_dong' => (string) ($hd->ma_hop_dong ?? ''),
                        'ten_khach_hang' => (string) ($hd->ten_khach_hang ?? ''),
                        'buoi_index' => (int) $sessionIndex,
                        'ten_buoi' => $tenBuoi,
                        'ngay_chup' => $ngayChup,
                        'so_diem_chup' => $diem,
                        'loai_quay_chup_id' => $loaiId,
                        'loai_quay_chup_ten' => $loaiTen,
                        'don_gia' => $donGia,
                        'thanh_tien' => $thanhTien,
                    ];
                }
            }

            if (! $isThoEditHopDong) {
                continue;
            }

            if (! isset($map[$dateKey])) {
                $map[$dateKey] = $this->emptySanXuat($withChiTiet);
            }

            $editBreakdown = $this->resolveSoAnhChinhSuaFromCombos($hd);
            $soAnh = (int) ($editBreakdown['tong_so_anh'] ?? 0);
            $thanhTienEdit = round($soAnh * $donGiaEdit, 2);

            $map[$dateKey]['so_job']['edit'] = (int) ($map[$dateKey]['so_job']['edit'] ?? 0) + 1;
            if ($thanhTienEdit > 0) {
                $map[$dateKey]['edit'] = round($map[$dateKey]['edit'] + $thanhTienEdit, 2);
            }

            if ($withChiTiet) {
                $map[$dateKey]['chi_tiet']['edit'][] = [
                    'hop_dong_id' => (int) $hd->id,
                    'ma_hop_dong' => (string) ($hd->ma_hop_dong ?? ''),
                    'ten_khach_hang' => (string) ($hd->ten_khach_hang ?? ''),
                    'so_anh_chinh_sua' => $soAnh,
                    'don_gia' => $donGiaEdit,
                    'thanh_tien' => $thanhTienEdit,
                    'combos' => $editBreakdown['combos'],
                ];
            }
        }

        return $map;
    }

    /**
     * Tổng số ảnh chỉnh sửa từ combos của HĐ:
     * Σ (so_luong × dich_vu_danh_sach_dich_nhom_dich_vu.so_anh_chinh_sua).
     *
     * @return array{tong_so_anh: int, combos: list<array{combo_id: int, ma_nhom: string, ten_nhom: string, so_luong: int, so_anh_chinh_sua: int, tong_anh: int}>}
     */
    private function resolveSoAnhChinhSuaFromCombos(HopDongSuDungDichVu $hd): array
    {
        $combos = [];
        $tong = 0;

        foreach ($hd->combos as $row) {
            $combo = $row->combo;
            $soLuong = max(1, (int) ($row->so_luong ?? 1));
            $soAnh = (int) ($combo?->so_anh_chinh_sua ?? 0);
            $tongAnh = $soAnh * $soLuong;
            $tong += $tongAnh;

            $combos[] = [
                'combo_id' => (int) ($row->combo_id ?? $combo?->id ?? 0),
                'ma_nhom' => (string) ($combo?->ma_nhom ?? ''),
                'ten_nhom' => (string) ($combo?->ten_nhom ?? ''),
                'so_luong' => $soLuong,
                'so_anh_chinh_sua' => $soAnh,
                'tong_anh' => $tongAnh,
            ];
        }

        return [
            'tong_so_anh' => $tong,
            'combos' => $combos,
        ];
    }

    /**
     * Ngày hoàn tất sản xuất từ envelope điều phối (Y-m-d, Asia/Ho_Chi_Minh).
     *
     * @param  array<string, mixed>  $envelope
     */
    private function resolveNgayHoanTatSanXuat(array $envelope): ?string
    {
        $raw = $envelope[HopDongSuDungDichVu::THOI_GIAN_HOAN_TAT_SAN_XUAT_KEY] ?? null;
        if ($raw === null || $raw === '') {
            return null;
        }

        try {
            return Carbon::parse($raw)->timezone(self::TIMEZONE)->toDateString();
        } catch (\Throwable) {
            $text = substr((string) $raw, 0, 10);

            return preg_match('/^\d{4}-\d{2}-\d{2}$/', $text) ? $text : null;
        }
    }

    /**
     * Số điểm chụp của một buổi (clamp 1–3). 0 nếu thiếu/không hợp lệ.
     *
     * @param  array<string, mixed>  $session
     */
    private function resolveSoDiemChupFromSession(array $session): int
    {
        $raw = HopDongSuDungDichVu::dieuPhoiGiaTri($session, 'so_diem_chup');
        if ($raw === null) {
            $fallback = $session['so_diem_chup'] ?? null;
            if (! is_array($fallback)) {
                $raw = $fallback;
            }
        }

        $diem = (int) $raw;
        if ($diem <= 0) {
            return 0;
        }

        return min(3, max(1, $diem));
    }

    /**
     * id loại quay chụp (danh_muc_loai_quay_chup) của buổi.
     *
     * @param  array<string, mixed>  $session
     */
    private function resolveLoaiQuayChupId(array $session): ?int
    {
        $raw = HopDongSuDungDichVu::dieuPhoiGiaTri($session, 'loai_quay_chup');
        if ($raw === null) {
            $raw = $session['loai_quay_chup'] ?? null;
        }

        if (is_array($raw)) {
            $id = $raw['id'] ?? $raw['danh_muc_loai_quay_chup_id'] ?? $raw['ma_dich_vu'] ?? null;
            if ($id === null || $id === '') {
                return null;
            }
            $n = (int) $id;

            return $n > 0 ? $n : null;
        }

        if (is_numeric($raw)) {
            $n = (int) $raw;

            return $n > 0 ? $n : null;
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $session
     */
    private function resolveLoaiQuayChupTen(array $session): string
    {
        $raw = HopDongSuDungDichVu::dieuPhoiGiaTri($session, 'loai_quay_chup');
        if ($raw === null) {
            $raw = $session['loai_quay_chup'] ?? null;
        }
        if (! is_array($raw)) {
            return '';
        }

        return trim((string) ($raw['ten_dich_vu'] ?? $raw['ten'] ?? ''));
    }

    /**
     * @param  array<string, mixed>  $session
     */
    private function resolveTenBuoi(array $session, int $index): string
    {
        $raw = HopDongSuDungDichVu::dieuPhoiGiaTri($session, 'ten_lich');
        if (is_string($raw) && trim($raw) !== '') {
            return trim($raw);
        }

        foreach (['ten_lich', '_ten_lich'] as $key) {
            $direct = $session[$key] ?? null;
            if (is_string($direct) && trim($direct) !== '') {
                return trim($direct);
            }
        }

        return 'Lịch quay chụp '.($index + 1);
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
     * @return array{make: float, chup: float, quay_phim: float, edit: float, so_job: array{make: int, chup: int, quay_phim: int, edit: int}, chi_tiet?: array<string, list<array<string, mixed>>>}
     */
    private function emptySanXuat(bool $withChiTiet = false): array
    {
        $result = [
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

        if ($withChiTiet) {
            $result['chi_tiet'] = [
                'make' => [],
                'chup' => [],
                'quay_phim' => [],
                'edit' => [],
            ];
        }

        return $result;
    }
}
