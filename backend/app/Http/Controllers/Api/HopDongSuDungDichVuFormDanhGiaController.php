<?php

namespace App\Http\Controllers\Api;

use App\Models\CauHinhFormDanhGiaMau;
use App\Models\HopDongSuDungDichVuFormDanhGia;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class HopDongSuDungDichVuFormDanhGiaController extends BaseApiController
{
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
