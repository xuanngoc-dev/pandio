<?php

namespace App\Http\Controllers\Api;

use App\Models\CauHinhFormDanhGiaMau;
use App\Models\HopDongSuDungDichVu;
use App\Models\HopDongSuDungDichVuFormDanhGia;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class HopDongSuDungDichVuFormDanhGiaController extends BaseApiController
{
    /**
     * Danh sách đánh giá đã nộp — lọc theo form (bắt buộc) + từ khoá.
     *
     * Query: form_danh_gia_id (required), keyword, page, per_page
     */
    public function index(Request $request): JsonResponse
    {
        return $this->handleApi(function () use ($request) {
            $validated = $request->validate([
                'form_danh_gia_id' => [
                    'required',
                    'integer',
                    'exists:cau_hinh_form_danh_gia_mau,id',
                ],
                'keyword' => ['sometimes', 'nullable', 'string', 'max:255'],
                'page' => ['sometimes', 'integer', 'min:1'],
                'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            ]);

            $perPage = $validated['per_page'] ?? 10;
            $keyword = trim((string) ($validated['keyword'] ?? ''));

            $query = HopDongSuDungDichVuFormDanhGia::query()
                ->with([
                    'formDanhGia:id,ten_form,slug',
                    'hopDong:id,ma_hop_dong,ten_khach_hang,sdt_khach_hang',
                ])
                ->where('form_danh_gia_id', $validated['form_danh_gia_id'])
                ->whereNotNull('noi_dung_danh_gia')
                ->when($keyword !== '', function ($q) use ($keyword) {
                    $q->where(function ($inner) use ($keyword) {
                        $inner->where('noi_dung_danh_gia', 'like', "%{$keyword}%")
                            ->orWhereHas('hopDong', function ($hopDong) use ($keyword) {
                                $hopDong->where('ma_hop_dong', 'like', "%{$keyword}%")
                                    ->orWhere('ten_khach_hang', 'like', "%{$keyword}%")
                                    ->orWhere('sdt_khach_hang', 'like', "%{$keyword}%");
                            })
                            ->orWhereHas('formDanhGia', function ($form) use ($keyword) {
                                $form->where('ten_form', 'like', "%{$keyword}%");
                            });
                    });
                })
                ->orderByDesc('updated_at');

            return response()->json($query->paginate($perPage));
        }, 'lấy danh sách đánh giá hợp đồng');
    }

    /**
     * Tạo link đánh giá cho hợp đồng + form mẫu.
     * Không cho trùng cùng cặp hop_dong_danh_gia_id + form_danh_gia_id.
     */
    public function store(Request $request): JsonResponse
    {
        return $this->handleApi(function () use ($request) {
            $validated = $request->validate(
                [
                    'hop_dong_danh_gia_id' => [
                        'required',
                        'integer',
                        'exists:hop_dong_su_dung_dich_vu,id',
                    ],
                    'form_danh_gia_id' => [
                        'required',
                        'integer',
                        'exists:cau_hinh_form_danh_gia_mau,id',
                        Rule::unique('hop_dong_su_dung_dich_vu_form_danh_gia', 'form_danh_gia_id')
                            ->where(
                                fn ($query) => $query->where(
                                    'hop_dong_danh_gia_id',
                                    $request->input('hop_dong_danh_gia_id')
                                )
                            ),
                    ],
                ],
                [
                    'form_danh_gia_id.unique' => 'Link đánh giá cho hợp đồng và form này đã tồn tại.',
                ]
            );

            $hopDong = HopDongSuDungDichVu::query()->findOrFail($validated['hop_dong_danh_gia_id']);
            if ($hopDong->trang_thai !== 'hoan_thanh') {
                abort(422, 'Chỉ hợp đồng ở trạng thái hoàn thành mới được tạo link đánh giá.');
            }

            $item = HopDongSuDungDichVuFormDanhGia::create([
                'hop_dong_danh_gia_id' => $validated['hop_dong_danh_gia_id'],
                'form_danh_gia_id' => $validated['form_danh_gia_id'],
                'noi_dung_danh_gia' => null,
            ]);

            $item->load('formDanhGia:id,ten_form,slug');

            return response()->json($item, 201);
        }, 'tạo link đánh giá hợp đồng');
    }

    /**
     * Xóa nội dung đánh giá đã nộp (giữ nguyên bản ghi link).
     */
    public function xoaNoiDung(int $id): JsonResponse
    {
        return $this->handleApi(function () use ($id) {
            $item = HopDongSuDungDichVuFormDanhGia::query()->findOrFail($id);

            if ($item->noi_dung_danh_gia === null) {
                abort(422, 'Đánh giá này chưa có nội dung để xóa.');
            }

            $item->update([
                'noi_dung_danh_gia' => null,
            ]);

            return response()->json([
                'message' => 'Đã xóa nội dung đánh giá.',
                'id' => $item->id,
            ]);
        }, 'xóa nội dung đánh giá hợp đồng');
    }

    /**
     * Khách hàng nộp đánh giá theo slug form + hop_dong_danh_gia_id (công khai).
     */
    public function nopDanhGia(Request $request, string $slug): JsonResponse
    {
        return $this->handleApi(function () use ($request, $slug) {
            $validated = $request->validate([
                'hop_dong_danh_gia_id' => [
                    'required',
                    'integer',
                    'exists:hop_dong_su_dung_dich_vu,id',
                ],
                'noi_dung_danh_gia' => ['required', 'array', 'min:1'],
                'noi_dung_danh_gia.*.cau_hoi' => ['required', 'string', 'max:1000'],
                'noi_dung_danh_gia.*.loai_danh_gia' => ['required', 'string', Rule::in(['diem', 'van_ban'])],
                'noi_dung_danh_gia.*.thong_tin_danh_gia' => ['nullable', 'string', 'max:255'],
                'noi_dung_danh_gia.*.gia_tri' => ['nullable'],
            ]);

            $form = CauHinhFormDanhGiaMau::query()
                ->where('slug', $slug)
                ->firstOrFail();

            $item = HopDongSuDungDichVuFormDanhGia::query()
                ->where('hop_dong_danh_gia_id', $validated['hop_dong_danh_gia_id'])
                ->where('form_danh_gia_id', $form->id)
                ->first();

            if (! $item) {
                abort(404, 'Không tìm thấy link đánh giá cho hợp đồng này.');
            }

            if (! empty($item->noi_dung_danh_gia)) {
                abort(422, 'Form đánh giá này đã được gửi trước đó.');
            }

            $item->update([
                'noi_dung_danh_gia' => array_values($validated['noi_dung_danh_gia']),
            ]);

            return response()->json([
                'message' => 'Đã ghi nhận đánh giá.',
                'id' => $item->id,
            ]);
        }, 'nộp đánh giá khách hàng');
    }
}
