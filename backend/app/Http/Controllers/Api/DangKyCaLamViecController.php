<?php

namespace App\Http\Controllers\Api;

use App\Models\DangKyCaLamViec;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class DangKyCaLamViecController extends BaseApiController
{
    private const TIMEZONE = 'Asia/Ho_Chi_Minh';

    /**
     * Danh sách đăng ký ca — lọc theo khoảng ngày / nhân viên.
     *
     * Query: tu_ngay, den_ngay, nguoi_dung_id
     */
    public function index(Request $request): JsonResponse
    {
        return $this->handleApi(function () use ($request) {
            $validated = $request->validate([
                'tu_ngay' => ['sometimes', 'nullable', 'date'],
                'den_ngay' => ['sometimes', 'nullable', 'date', 'after_or_equal:tu_ngay'],
                'nguoi_dung_id' => ['sometimes', 'nullable', 'integer', 'exists:users,id'],
            ]);

            $query = DangKyCaLamViec::query()
                ->with([
                    'caLam:id,ten_ca,gio_bat_dau,gio_ket_thuc,trang_thai',
                    'nguoiDung:id,name,email',
                ])
                ->when(
                    ! empty($validated['tu_ngay']),
                    fn ($q) => $q->whereDate('ngay_lam', '>=', $validated['tu_ngay'])
                )
                ->when(
                    ! empty($validated['den_ngay']),
                    fn ($q) => $q->whereDate('ngay_lam', '<=', $validated['den_ngay'])
                )
                ->when(
                    ! empty($validated['nguoi_dung_id']),
                    fn ($q) => $q->where('nguoi_dung_id', $validated['nguoi_dung_id'])
                )
                ->orderBy('ngay_lam')
                ->orderBy('nguoi_dung_id');

            return response()->json($query->get());

        }, 'lấy danh sách đăng ký ca làm việc');
    }

    /**
     * Chi tiết một đăng ký ca.
     */
    public function show(DangKyCaLamViec $dang_ky_ca_lam_viec): JsonResponse
    {
        return $this->handleApi(function () use ($dang_ky_ca_lam_viec) {
            $dang_ky_ca_lam_viec->load([
                'caLam:id,ten_ca,gio_bat_dau,gio_ket_thuc,trang_thai',
                'nguoiDung:id,name,email',
            ]);

            return response()->json($dang_ky_ca_lam_viec);

        }, 'lấy chi tiết đăng ký ca làm việc');
    }

    /**
     * Tạo / cập nhật đăng ký ca (upsert theo nhân viên + ngày).
     */
    public function store(Request $request): JsonResponse
    {
        return $this->handleApi(function () use ($request) {
            $validated = $request->validate([
                'ca_lam_id' => [
                    'required',
                    'integer',
                    Rule::exists('cau_hinh_ca_lam_viec', 'id')->where('trang_thai', 'co'),
                ],
                'nguoi_dung_id' => ['required', 'integer', 'exists:users,id'],
                'ngay_lam' => ['required', 'date'],
            ]);

            $this->assertNgayLamEditable($request, $validated['ngay_lam']);

            $item = DangKyCaLamViec::updateOrCreate(
                [
                    'nguoi_dung_id' => $validated['nguoi_dung_id'],
                    'ngay_lam' => $validated['ngay_lam'],
                ],
                [
                    'ca_lam_id' => $validated['ca_lam_id'],
                ]
            );

            $item->load([
                'caLam:id,ten_ca,gio_bat_dau,gio_ket_thuc,trang_thai',
                'nguoiDung:id,name,email',
            ]);

            return response()->json($item, $item->wasRecentlyCreated ? 201 : 200);

        }, 'tạo đăng ký ca làm việc');
    }

    /**
     * Cập nhật đăng ký ca.
     */
    public function update(Request $request, DangKyCaLamViec $dang_ky_ca_lam_viec): JsonResponse
    {
        return $this->handleApi(function () use ($request, $dang_ky_ca_lam_viec) {
            $validated = $this->validatePayload($request, $dang_ky_ca_lam_viec->id);

            $this->assertNgayLamEditable($request, $validated['ngay_lam']);
            $this->assertNgayLamEditable($request, $dang_ky_ca_lam_viec->ngay_lam);

            $dang_ky_ca_lam_viec->update($validated);

            return response()->json($dang_ky_ca_lam_viec->fresh([
                'caLam:id,ten_ca,gio_bat_dau,gio_ket_thuc,trang_thai',
                'nguoiDung:id,name,email',
            ]));

        }, 'cập nhật đăng ký ca làm việc');
    }

    /**
     * Đồng bộ đăng ký ca trong tuần (mảng items).
     * Mỗi item: nguoi_dung_id, ngay_lam, ca_lam_id (null = xóa đăng ký).
     */
    public function syncTuan(Request $request): JsonResponse
    {
        return $this->handleApi(function () use ($request) {
            $validated = $request->validate([
                'tu_ngay' => ['required', 'date'],
                'den_ngay' => ['required', 'date', 'after_or_equal:tu_ngay'],
                'items' => ['required', 'array'],
                'items.*.nguoi_dung_id' => ['required', 'integer', 'exists:users,id'],
                'items.*.ngay_lam' => [
                    'required',
                    'date',
                    'after_or_equal:tu_ngay',
                    'before_or_equal:den_ngay',
                ],
                'items.*.ca_lam_id' => [
                    'nullable',
                    'integer',
                    Rule::exists('cau_hinh_ca_lam_viec', 'id')->where('trang_thai', 'co'),
                ],
            ]);

            foreach ($validated['items'] as $index => $item) {
                try {
                    $this->assertNgayLamEditable($request, $item['ngay_lam']);
                } catch (ValidationException $e) {
                    throw ValidationException::withMessages([
                        "items.{$index}.ngay_lam" => $e->errors()['ngay_lam'] ?? [
                            $this->ngayLamErrorMessage($request),
                        ],
                    ]);
                }
            }

            $result = DB::transaction(function () use ($validated) {
                $saved = [];

                foreach ($validated['items'] as $item) {
                    $nguoiDungId = $item['nguoi_dung_id'];
                    $ngayLam = $item['ngay_lam'];
                    $caLamId = $item['ca_lam_id'] ?? null;

                    if ($caLamId === null) {
                        DangKyCaLamViec::query()
                            ->where('nguoi_dung_id', $nguoiDungId)
                            ->whereDate('ngay_lam', $ngayLam)
                            ->delete();

                        continue;
                    }

                    $row = DangKyCaLamViec::updateOrCreate(
                        [
                            'nguoi_dung_id' => $nguoiDungId,
                            'ngay_lam' => $ngayLam,
                        ],
                        [
                            'ca_lam_id' => $caLamId,
                        ]
                    );

                    $saved[] = $row->id;
                }

                return DangKyCaLamViec::query()
                    ->with([
                        'caLam:id,ten_ca,gio_bat_dau,gio_ket_thuc,trang_thai',
                        'nguoiDung:id,name,email',
                    ])
                    ->whereDate('ngay_lam', '>=', $validated['tu_ngay'])
                    ->whereDate('ngay_lam', '<=', $validated['den_ngay'])
                    ->orderBy('ngay_lam')
                    ->orderBy('nguoi_dung_id')
                    ->get();
            });

            return response()->json($result);

        }, 'đồng bộ đăng ký ca theo tuần');
    }

    /**
     * Xóa đăng ký ca.
     */
    public function destroy(Request $request, DangKyCaLamViec $dang_ky_ca_lam_viec): JsonResponse
    {
        return $this->handleApi(function () use ($request, $dang_ky_ca_lam_viec) {
            $this->assertNgayLamEditable($request, $dang_ky_ca_lam_viec->ngay_lam);

            $dang_ky_ca_lam_viec->delete();

            return response()->json(['message' => 'Đã xóa đăng ký ca.']);

        }, 'xóa đăng ký ca làm việc');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatePayload(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'ca_lam_id' => [
                'required',
                'integer',
                Rule::exists('cau_hinh_ca_lam_viec', 'id')->where('trang_thai', 'co'),
            ],
            'nguoi_dung_id' => [
                'required',
                'integer',
                'exists:users,id',
                Rule::unique('dang_ky_ca_lam_viec', 'nguoi_dung_id')
                    ->where(fn ($q) => $q->whereDate('ngay_lam', $request->input('ngay_lam')))
                    ->ignore($ignoreId),
            ],
            'ngay_lam' => ['required', 'date'],
        ]);
    }

    private function isAdmin(Request $request): bool
    {
        return ($request->user()?->role ?? null) === 'admin';
    }

    private function todayDate(): Carbon
    {
        return Carbon::now(self::TIMEZONE)->startOfDay();
    }

    /**
     * Ngày sớm nhất được phép đăng ký/sửa/xóa ca.
     * Admin: đầu tháng hiện tại; role khác: ngày mai.
     */
    private function minEditableDate(Request $request): Carbon
    {
        $today = $this->todayDate();

        if ($this->isAdmin($request)) {
            return $today->copy()->startOfMonth();
        }

        return $today->copy()->addDay();
    }

    private function ngayLamErrorMessage(Request $request): string
    {
        return $this->isAdmin($request)
            ? 'Không được đăng ký ca cho ngày trước tháng hiện tại.'
            : 'Chỉ được đăng ký ca từ ngày mai trở đi.';
    }

    private function assertNgayLamEditable(Request $request, mixed $ngayLam): void
    {
        $date = Carbon::parse($ngayLam, self::TIMEZONE)->startOfDay();
        $minDate = $this->minEditableDate($request);

        if ($date->lt($minDate)) {
            throw ValidationException::withMessages([
                'ngay_lam' => [$this->ngayLamErrorMessage($request)],
            ]);
        }
    }
}
