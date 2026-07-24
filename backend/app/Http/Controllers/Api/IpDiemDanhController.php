<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\IpDiemDanh;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class IpDiemDanhController extends Controller
{
    /**
     * Danh sách IP điểm danh — phân trang + tìm kiếm.
     *
     * Query: page, per_page, keyword, trang_thai
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'keyword' => ['sometimes', 'nullable', 'string', 'max:255'],
            'trang_thai' => ['sometimes', 'nullable', 'string', Rule::in(['active', 'inactive'])],
        ]);

        $perPage = $validated['per_page'] ?? 10;
        $keyword = trim((string) ($validated['keyword'] ?? ''));
        $trangThai = $validated['trang_thai'] ?? null;

        $query = IpDiemDanh::query()
            ->when($keyword !== '', function ($q) use ($keyword) {
                $q->where(function ($inner) use ($keyword) {
                    $inner->where('ten_ip', 'like', "%{$keyword}%")
                        ->orWhere('dia_chi_ip', 'like', "%{$keyword}%")
                        ->orWhere('ghi_chu', 'like', "%{$keyword}%");
                });
            })
            ->when($trangThai, fn ($q) => $q->where('trang_thai', $trangThai))
            ->orderByDesc('id');

        return response()->json($query->paginate($perPage));
    }

    /**
     * Chi tiết một IP điểm danh.
     */
    public function show(IpDiemDanh $ip_diem_danh): JsonResponse
    {
        return response()->json($ip_diem_danh);
    }

    /**
     * Tạo IP điểm danh mới.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ten_ip' => ['required', 'string', 'max:255'],
            'dia_chi_ip' => ['required', 'string', 'max:45', 'unique:ip_diem_danh,dia_chi_ip'],
            'ghi_chu' => ['nullable', 'string'],
            'trang_thai' => ['required', 'string', Rule::in(['active', 'inactive'])],
        ]);

        $ipDiemDanh = IpDiemDanh::create($validated);

        return response()->json($ipDiemDanh, 201);
    }

    /**
     * Cập nhật IP điểm danh.
     */
    public function update(Request $request, IpDiemDanh $ip_diem_danh): JsonResponse
    {
        $validated = $request->validate([
            'ten_ip' => ['required', 'string', 'max:255'],
            'dia_chi_ip' => [
                'required',
                'string',
                'max:45',
                Rule::unique('ip_diem_danh', 'dia_chi_ip')->ignore($ip_diem_danh->id),
            ],
            'ghi_chu' => ['nullable', 'string'],
            'trang_thai' => ['required', 'string', Rule::in(['active', 'inactive'])],
        ]);

        $ip_diem_danh->update($validated);

        return response()->json($ip_diem_danh->fresh());
    }

    /**
     * Xóa IP điểm danh.
     */
    public function destroy(IpDiemDanh $ip_diem_danh): JsonResponse
    {
        $ip_diem_danh->delete();

        return response()->json(['message' => 'Đã xóa IP điểm danh.']);
    }
}
