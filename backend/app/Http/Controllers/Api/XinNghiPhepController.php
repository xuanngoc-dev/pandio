<?php

namespace App\Http\Controllers\Api;

use App\Models\DiemDanh;
use App\Models\XinNghiPhep;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class XinNghiPhepController extends BaseApiController
{
    private const LOAI_NGHI_PHEP = [
        'di_muon',
        've_som',
        'nghi_nua_ngay',
        'nghi_1_ngay',
        'nghi_nhieu_ngay',
    ];

    private const BUOI_NGHI = ['sang', 'chieu'];

    private const TRANG_THAI = ['cho_duyet', 'da_duyet', 'tu_choi'];

    /**
     * Danh sách xin nghỉ phép — phân trang + lọc.
     *
     * Query: page, per_page, keyword, user_id, loai_nghi_phep, trang_thai, tu_ngay, den_ngay
     * tu_ngay/den_ngay: lọc đơn có khoảng nghỉ giao với khoảng ngày.
     */
    public function index(Request $request): JsonResponse
    {
        return $this->handleApi(function () use ($request) {
            $validated = $request->validate([
                'page' => ['sometimes', 'integer', 'min:1'],
                'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
                'keyword' => ['sometimes', 'nullable', 'string', 'max:255'],
                'user_id' => ['sometimes', 'nullable', 'integer', 'exists:users,id'],
                'loai_nghi_phep' => ['sometimes', 'nullable', 'string', Rule::in(self::LOAI_NGHI_PHEP)],
                'trang_thai' => ['sometimes', 'nullable', 'string', Rule::in(self::TRANG_THAI)],
                'tu_ngay' => ['sometimes', 'nullable', 'date'],
                'den_ngay' => ['sometimes', 'nullable', 'date', 'after_or_equal:tu_ngay'],
            ]);

            $perPage = $validated['per_page'] ?? 10;
            $keyword = trim((string) ($validated['keyword'] ?? ''));

            $query = XinNghiPhep::query()
                ->with([
                    'user:id,name,email',
                    'nguoiDuyet:id,name,email',
                ])
                ->when($keyword !== '', function ($q) use ($keyword) {
                    $q->where(function ($inner) use ($keyword) {
                        $inner->where('ly_do', 'like', "%{$keyword}%")
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
                    ! empty($validated['loai_nghi_phep']),
                    fn ($q) => $q->where('loai_nghi_phep', $validated['loai_nghi_phep'])
                )
                ->when(
                    ! empty($validated['trang_thai']),
                    fn ($q) => $q->where('trang_thai', $validated['trang_thai'])
                )
                ->when(
                    ! empty($validated['tu_ngay']) || ! empty($validated['den_ngay']),
                    function ($q) use ($validated) {
                        $tuNgay = $validated['tu_ngay'] ?? null;
                        $denNgay = $validated['den_ngay'] ?? null;

                        // Giao khoảng: bat_dau <= den_ngay AND ket_thuc >= tu_ngay
                        if ($denNgay) {
                            $q->whereDate('ngay_bat_dau', '<=', $denNgay);
                        }
                        if ($tuNgay) {
                            $q->whereRaw(
                                'COALESCE(ngay_ket_thuc, ngay_bat_dau) >= ?',
                                [$tuNgay]
                            );
                        }
                    }
                )
                ->orderByDesc('id');

            return response()->json($query->paginate($perPage));

        }, 'lấy danh sách đơn xin nghỉ phép');
    }

    /**
     * Chi tiết một đơn xin nghỉ phép.
     */
    public function show(XinNghiPhep $xin_nghi_phep): JsonResponse
    {
        return $this->handleApi(function () use ($xin_nghi_phep) {
            $xin_nghi_phep->load([
                'user:id,name,email',
                'nguoiDuyet:id,name,email',
            ]);

            return response()->json($xin_nghi_phep);

        }, 'lấy chi tiết đơn xin nghỉ phép');
    }

    /**
     * Tạo đơn xin nghỉ phép.
     */
    public function store(Request $request): JsonResponse
    {
        return $this->handleApi(function () use ($request) {
            $validated = $this->validatePayload($request);
            $validated['trang_thai'] = 'cho_duyet';
            $validated['nguoi_duyet_id'] = null;

            $xinNghiPhep = XinNghiPhep::create($validated);
            $xinNghiPhep->load([
                'user:id,name,email',
                'nguoiDuyet:id,name,email',
            ]);

            return response()->json($xinNghiPhep, 201);

        }, 'tạo đơn xin nghỉ phép');
    }

    /**
     * Cập nhật đơn xin nghỉ phép (không cập nhật trạng thái / người duyệt).
     */
    public function update(Request $request, XinNghiPhep $xin_nghi_phep): JsonResponse
    {
        return $this->handleApi(function () use ($request, $xin_nghi_phep) {
            $validated = $this->validatePayload($request);
            unset($validated['trang_thai'], $validated['nguoi_duyet_id']);

            $xin_nghi_phep->update($validated);

            return response()->json($xin_nghi_phep->fresh([
                'user:id,name,email',
                'nguoiDuyet:id,name,email',
            ]));

        }, 'cập nhật đơn xin nghỉ phép');
    }

    /**
     * Duyệt đơn nghỉ phép — lưu người duyệt = user đang đăng nhập.
     * Nếu duyệt đi muộn / về sớm thì cập nhật tiền phạt tương ứng trên diem_danh = 0.
     */
    public function duyet(XinNghiPhep $xin_nghi_phep): JsonResponse
    {
        return $this->handleApi(function () use ($xin_nghi_phep) {
            if ($xin_nghi_phep->trang_thai !== 'cho_duyet') {
                throw ValidationException::withMessages([
                    'trang_thai' => ['Chỉ có thể duyệt đơn đang chờ duyệt.'],
                ]);
            }

            $xin_nghi_phep->update([
                'trang_thai' => 'da_duyet',
                'nguoi_duyet_id' => auth()->id(),
            ]);

            $this->capNhatDiemDanhKhiDuyet($xin_nghi_phep);

            return response()->json($xin_nghi_phep->fresh([
                'user:id,name,email',
                'nguoiDuyet:id,name,email',
            ]));

        }, 'duyệt đơn xin nghỉ phép');
    }

    /**
     * Khi duyệt đi muộn / về sớm: miễn tiền phạt trên bản ghi điểm danh cùng ngày.
     */
    private function capNhatDiemDanhKhiDuyet(XinNghiPhep $xinNghiPhep): void
    {
        if (! in_array($xinNghiPhep->loai_nghi_phep, ['di_muon', 've_som'], true)) {
            return;
        }

        $ngay = $xinNghiPhep->ngay_bat_dau?->toDateString();
        if (! $ngay) {
            return;
        }

        $diemDanh = DiemDanh::query()
            ->where('user_id', $xinNghiPhep->user_id)
            ->whereDate('ngay_lam', $ngay)
            ->first();

        if (! $diemDanh) {
            return;
        }

        $payload = [];

        if ($xinNghiPhep->loai_nghi_phep === 'di_muon') {
            $payload['tien_phat_di_muon'] = 0;
            $payload['di_muon'] = 'khong';
            $payload['thoi_gian_di_muon'] = 0;
        }

        if ($xinNghiPhep->loai_nghi_phep === 've_som') {
            $payload['tien_phat_ve_som'] = 0;
            $payload['ve_som'] = 'khong';
            $payload['thoi_gian_ve_som'] = 0;
        }

        if ($xinNghiPhep->ly_do) {
            $payload['ly_do'] = $xinNghiPhep->ly_do;
        }

        $diemDanh->update($payload);
    }

    /**
     * Từ chối đơn nghỉ phép — lưu người duyệt = user đang đăng nhập.
     */
    public function tuChoi(XinNghiPhep $xin_nghi_phep): JsonResponse
    {
        return $this->handleApi(function () use ($xin_nghi_phep) {
            if ($xin_nghi_phep->trang_thai !== 'cho_duyet') {
                throw ValidationException::withMessages([
                    'trang_thai' => ['Chỉ có thể từ chối đơn đang chờ duyệt.'],
                ]);
            }

            $xin_nghi_phep->update([
                'trang_thai' => 'tu_choi',
                'nguoi_duyet_id' => auth()->id(),
            ]);

            return response()->json($xin_nghi_phep->fresh([
                'user:id,name,email',
                'nguoiDuyet:id,name,email',
            ]));

        }, 'từ chối đơn xin nghỉ phép');
    }

    /**
     * Duyệt nhiều đơn nghỉ phép đang chờ duyệt.
     */
    public function bulkDuyet(Request $request): JsonResponse
    {
        return $this->handleApi(function () use ($request) {
            $validated = $request->validate([
                'ids' => ['required', 'array', 'min:1'],
                'ids.*' => ['integer', 'exists:xin_nghi_phep,id'],
            ]);

            $items = XinNghiPhep::query()
                ->whereIn('id', $validated['ids'])
                ->where('trang_thai', 'cho_duyet')
                ->get();

            $updated = 0;
            foreach ($items as $item) {
                $item->update([
                    'trang_thai' => 'da_duyet',
                    'nguoi_duyet_id' => auth()->id(),
                ]);
                $this->capNhatDiemDanhKhiDuyet($item);
                $updated++;
            }

            return response()->json([
                'message' => "Đã duyệt {$updated} đơn xin nghỉ phép.",
                'updated' => $updated,
            ]);
        }, 'duyệt nhiều đơn xin nghỉ phép');
    }

    /**
     * Từ chối nhiều đơn nghỉ phép đang chờ duyệt.
     */
    public function bulkTuChoi(Request $request): JsonResponse
    {
        return $this->handleApi(function () use ($request) {
            $validated = $request->validate([
                'ids' => ['required', 'array', 'min:1'],
                'ids.*' => ['integer', 'exists:xin_nghi_phep,id'],
            ]);

            $updated = XinNghiPhep::query()
                ->whereIn('id', $validated['ids'])
                ->where('trang_thai', 'cho_duyet')
                ->update([
                    'trang_thai' => 'tu_choi',
                    'nguoi_duyet_id' => auth()->id(),
                ]);

            return response()->json([
                'message' => "Đã từ chối {$updated} đơn xin nghỉ phép.",
                'updated' => $updated,
            ]);
        }, 'từ chối nhiều đơn xin nghỉ phép');
    }

    /**
     * Xóa đơn xin nghỉ phép.
     */
    public function destroy(XinNghiPhep $xin_nghi_phep): JsonResponse
    {
        return $this->handleApi(function () use ($xin_nghi_phep) {
            $xin_nghi_phep->delete();

            return response()->json(['message' => 'Đã xóa đơn xin nghỉ phép.']);

        }, 'xóa đơn xin nghỉ phép');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatePayload(Request $request): array
    {
        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'loai_nghi_phep' => ['required', 'string', Rule::in(self::LOAI_NGHI_PHEP)],
            'buoi_nghi' => ['nullable', 'string', Rule::in(self::BUOI_NGHI)],
            'ngay_bat_dau' => ['required', 'date'],
            'ngay_ket_thuc' => ['nullable', 'date', 'after_or_equal:ngay_bat_dau'],
            'ly_do' => ['nullable', 'string'],
        ]);

        $errors = [];

        if ($validated['loai_nghi_phep'] === 'nghi_nua_ngay' && empty($validated['buoi_nghi'])) {
            $errors['buoi_nghi'] = ['Vui lòng chọn buổi nghỉ khi nghỉ nửa ngày.'];
        }

        if ($validated['loai_nghi_phep'] === 'nghi_nhieu_ngay' && empty($validated['ngay_ket_thuc'])) {
            $errors['ngay_ket_thuc'] = ['Vui lòng chọn ngày kết thúc khi nghỉ nhiều ngày.'];
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        if ($validated['loai_nghi_phep'] !== 'nghi_nhieu_ngay') {
            $validated['ngay_ket_thuc'] = $validated['ngay_bat_dau'];
        }

        if ($validated['loai_nghi_phep'] !== 'nghi_nua_ngay') {
            $validated['buoi_nghi'] = $validated['buoi_nghi'] ?? null;
        }

        return $validated;
    }
}
