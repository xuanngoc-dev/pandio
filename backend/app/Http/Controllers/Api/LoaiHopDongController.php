<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LoaiHopDong;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LoaiHopDongController extends Controller
{
    /**
     * Danh sách loại hợp đồng khách hàng — phân trang + tìm kiếm.
     *
     * Query: page, per_page, keyword, trang_thai
     */
    public function index(Request $request): JsonResponse
    {
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
    }

    /**
     * Chi tiết một loại hợp đồng khách hàng.
     */
    public function show(LoaiHopDong $loai_hop_dong): JsonResponse
    {
        return response()->json($loai_hop_dong);
    }

    /**
     * Tạo loại hợp đồng khách hàng mới.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $this->validatePayload($request);

        $item = LoaiHopDong::create($validated);

        return response()->json($item, 201);
    }

    /**
     * Cập nhật loại hợp đồng khách hàng.
     */
    public function update(Request $request, LoaiHopDong $loai_hop_dong): JsonResponse
    {
        $validated = $this->validatePayload($request, $loai_hop_dong->id);

        $loai_hop_dong->update($validated);

        return response()->json($loai_hop_dong->fresh());
    }

    /**
     * Xóa loại hợp đồng khách hàng.
     */
    public function destroy(LoaiHopDong $loai_hop_dong): JsonResponse
    {
        $loai_hop_dong->delete();

        return response()->json(['message' => 'Đã xóa loại hợp đồng.']);
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
                Rule::unique('loai_hop_dong', 'ma_hop_dong')->ignore($ignoreId),
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
                    'file',
                    'image',
                ]),
            ],
            'noi_dung.truong.*.bat_buoc' => ['nullable', 'boolean'],
            'noi_dung.truong.*.options' => ['nullable', 'array'],
            'noi_dung.truong.*.options.*.label' => ['required_with:noi_dung.truong.*.options', 'string', 'max:255'],
            'noi_dung.truong.*.options.*.value' => ['required_with:noi_dung.truong.*.options', 'string', 'max:255'],
            'trang_thai' => ['required', Rule::in(['hoat_dong', 'ngung_hoat_dong'])],
        ]);
    }
}
