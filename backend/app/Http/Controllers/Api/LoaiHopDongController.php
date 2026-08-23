<?php

namespace App\Http\Controllers\Api;

use App\Models\LoaiHopDong;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LoaiHopDongController extends BaseApiController
{
    /**
     * Danh sách loại hợp đồng khách hàng — phân trang + tìm kiếm.
     *
     * Query: page, per_page, keyword, trang_thai
     */
    public function index(Request $request): JsonResponse
    {
        return $this->handleApi(function () use ($request) {
            $validated = $request->validate([
                'page' => ['sometimes', 'integer', 'min:1'],
                'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
                'keyword' => ['sometimes', 'nullable', 'string', 'max:255'],
                'trang_thai' => ['sometimes', 'nullable', Rule::in(['hoat_dong', 'ngung_hoat_dong'])],
            ]);

            $perPage = $validated['per_page'] ?? 10;
            $keyword = trim((string) ($validated['keyword'] ?? ''));
            $trangThai = $validated['trang_thai'] ?? null;

            $query = LoaiHopDong::query()
                ->when($keyword !== '', function ($q) use ($keyword) {
                    $q->where(function ($inner) use ($keyword) {
                        $inner->where('ten_hop_dong', 'like', "%{$keyword}%")
                            ->orWhere('ma_hop_dong', 'like', "%{$keyword}%");
                    });
                })
                ->when($trangThai, fn ($q) => $q->where('trang_thai', $trangThai))
                ->orderByDesc('id');

            return response()->json($query->paginate($perPage));

        }, 'lấy danh sách loại hợp đồng');
    }

    /**
     * Chi tiết một loại hợp đồng khách hàng.
     */
    public function show(LoaiHopDong $loai_hop_dong): JsonResponse
    {
        return $this->handleApi(function () use ($loai_hop_dong) {
            return response()->json($loai_hop_dong);

        }, 'lấy chi tiết loại hợp đồng');
    }

    /**
     * Tạo loại hợp đồng khách hàng mới.
     */
    public function store(Request $request): JsonResponse
    {
        return $this->handleApi(function () use ($request) {
            $validated = $this->validatePayload($request);

            $item = LoaiHopDong::create($validated);

            return response()->json($item, 201);

        }, 'tạo loại hợp đồng');
    }

    /**
     * Cập nhật loại hợp đồng khách hàng.
     */
    public function update(Request $request, LoaiHopDong $loai_hop_dong): JsonResponse
    {
        return $this->handleApi(function () use ($request, $loai_hop_dong) {
            $validated = $this->validatePayload($request, $loai_hop_dong->id);

            $loai_hop_dong->update($validated);

            return response()->json($loai_hop_dong->fresh());

        }, 'cập nhật loại hợp đồng');
    }

    /**
     * Xóa loại hợp đồng khách hàng.
     */
    public function destroy(LoaiHopDong $loai_hop_dong): JsonResponse
    {
        return $this->handleApi(function () use ($loai_hop_dong) {
            $loai_hop_dong->delete();

            return response()->json(['message' => 'Đã xóa loại hợp đồng.']);

        }, 'xóa loại hợp đồng');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatePayload(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'ten_hop_dong' => ['required', 'string', 'max:255'],
            'ma_hop_dong' => [
                'required',
                'string',
                'max:50',
                Rule::unique('danh_muc_loai_hop_dong', 'ma_hop_dong')->ignore($ignoreId),
            ],
            'noi_dung' => ['nullable', 'array'],
            'noi_dung.truong' => ['nullable', 'array'],
            'noi_dung.truong.*.ten_truong' => ['required_with:noi_dung.truong', 'string', 'max:255'],
            'noi_dung.truong.*.key' => ['required_with:noi_dung.truong', 'string', 'max:100'],
            'noi_dung.truong.*.kieu' => [
                'required_with:noi_dung.truong',
                Rule::in([
                    'input',
                    'textarea',
                    'number',
                    'money',
                    'percent',
                    'email',
                    'phone',
                    'url',
                    'select',
                    'radio',
                    'checkbox',
                    'checkbox_group',
                    'switch',
                    'date',
                    'datetime',
                    'time',
                    'month',
                    'year',
                    // Giữ để tương thích dữ liệu cũ; UI không còn cho chọn mới
                    'file',
                    'image',
                ]),
            ],
            'noi_dung.truong.*.bat_buoc' => ['nullable', 'boolean'],
            'noi_dung.truong.*.options' => ['nullable', 'array'],
            'noi_dung.truong.*.options.*.label' => ['required_with:noi_dung.truong.*.options', 'string', 'max:255'],
            'noi_dung.truong.*.options.*.value' => ['required_with:noi_dung.truong.*.options', 'string', 'max:255'],
            'thong_tin_dieu_phoi' => ['nullable', 'array'],
            'thong_tin_dieu_phoi.*' => ['nullable', 'array'],
            'thong_tin_dieu_phoi.*.su_dung' => ['required', 'boolean'],
            'thong_tin_dieu_phoi.*.ten_thong_tin' => ['required', 'string', 'max:255'],
            'thong_tin_dieu_phoi.*.loai_du_lieu' => [
                'required',
                'string',
                Rule::in(['string', 'textarea', 'number', 'date', 'time', 'datetime', 'array']),
            ],
            'thong_tin_dieu_phoi.*.gia_tri' => ['nullable'],
            'thong_tin_dieu_phoi.*.gia_tri_toi_thieu' => ['nullable', 'numeric'],
            'thong_tin_dieu_phoi.*.gia_tri_toi_da' => ['nullable', 'numeric'],
            'trang_thai' => ['required', Rule::in(['hoat_dong', 'ngung_hoat_dong'])],
        ]);
    }
}
