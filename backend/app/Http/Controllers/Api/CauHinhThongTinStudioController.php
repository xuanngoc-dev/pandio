<?php

namespace App\Http\Controllers\Api;

use App\Models\CauHinhThongTinStudio;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class CauHinhThongTinStudioController extends BaseApiController
{
    /**
     * Danh sách thông tin studio — phân trang + tìm kiếm.
     *
     * Query: page, per_page, keyword, mac_dinh
     */
    public function index(Request $request): JsonResponse
    {
        return $this->handleApi(function () use ($request) {
            $validated = $request->validate([
                'page' => ['sometimes', 'integer', 'min:1'],
                'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
                'keyword' => ['sometimes', 'nullable', 'string', 'max:255'],
                'mac_dinh' => ['sometimes', 'nullable', 'string', Rule::in(['co', 'khong'])],
            ]);

            $perPage = $validated['per_page'] ?? 10;
            $keyword = trim((string) ($validated['keyword'] ?? ''));
            $macDinh = $validated['mac_dinh'] ?? null;

            $query = CauHinhThongTinStudio::query()
                ->when($keyword !== '', function ($q) use ($keyword) {
                    $q->where(function ($inner) use ($keyword) {
                        $inner->where('ten_studio', 'like', "%{$keyword}%")
                            ->orWhere('khau_hieu', 'like', "%{$keyword}%")
                            ->orWhere('dia_chi', 'like', "%{$keyword}%")
                            ->orWhere('email', 'like', "%{$keyword}%")
                            ->orWhere('so_dien_thoai', 'like', "%{$keyword}%")
                            ->orWhere('ma_so_thue', 'like', "%{$keyword}%");
                    });
                })
                ->when($macDinh, fn ($q) => $q->where('mac_dinh', $macDinh))
                ->orderByDesc('created_at')
                ->orderByDesc('id');

            return response()->json($query->paginate($perPage));

        }, 'lấy danh sách cấu hình studio');
    }

    /**
     * Chi tiết một thông tin studio.
     */
    public function show(CauHinhThongTinStudio $cau_hinh_thong_tin_studio): JsonResponse
    {
        return $this->handleApi(function () use ($cau_hinh_thong_tin_studio) {
            return response()->json($cau_hinh_thong_tin_studio);

        }, 'lấy chi tiết cấu hình studio');
    }

    /**
     * Upload logo studio vào storage/app/public/studio-logo.
     */
    public function uploadLogo(Request $request): JsonResponse
    {
        return $this->handleApi(function () use ($request) {
            $validated = $request->validate([
                'logo' => ['required', 'image', 'mimes:jpeg,jpg,png,webp,gif', 'max:2048'],
            ], [
                'logo.required' => 'Vui lòng chọn logo.',
                'logo.image' => 'File phải là hình ảnh.',
                'logo.mimes' => 'Chỉ chấp nhận jpeg, jpg, png, webp, gif.',
                'logo.max' => 'Logo tối đa 2MB.',
            ]);

            $path = $validated['logo']->store('studio-logo', 'public');

            return response()->json([
                'path' => $path,
                'url' => Storage::disk('public')->url($path),
            ], 201);

        }, 'upload logo cấu hình studio');
    }

    /**
     * Tạo thông tin studio mới.
     */
    public function store(Request $request): JsonResponse
    {
        return $this->handleApi(function () use ($request) {
            $validated = $this->validatePayload($request);

            $studio = DB::transaction(function () use ($validated) {
                if (($validated['mac_dinh'] ?? 'khong') === 'co') {
                    CauHinhThongTinStudio::query()->update(['mac_dinh' => 'khong']);
                }

                return CauHinhThongTinStudio::create($validated);
            });

            return response()->json($studio, 201);

        }, 'tạo cấu hình studio');
    }

    /**
     * Cập nhật thông tin studio.
     */
    public function update(Request $request, CauHinhThongTinStudio $cau_hinh_thong_tin_studio): JsonResponse
    {
        return $this->handleApi(function () use ($request, $cau_hinh_thong_tin_studio) {
            $validated = $this->validatePayload($request);
            $oldLogo = $cau_hinh_thong_tin_studio->logo;

            $studio = DB::transaction(function () use ($cau_hinh_thong_tin_studio, $validated) {
                if (($validated['mac_dinh'] ?? 'khong') === 'co') {
                    CauHinhThongTinStudio::query()
                        ->where('id', '!=', $cau_hinh_thong_tin_studio->id)
                        ->update(['mac_dinh' => 'khong']);
                }

                $cau_hinh_thong_tin_studio->update($validated);

                return $cau_hinh_thong_tin_studio->fresh();
            });

            if ($oldLogo && $oldLogo !== ($validated['logo'] ?? null) && Storage::disk('public')->exists($oldLogo)) {
                Storage::disk('public')->delete($oldLogo);
            }

            return response()->json($studio);

        }, 'cập nhật cấu hình studio');
    }

    /**
     * Xóa thông tin studio.
     */
    public function destroy(CauHinhThongTinStudio $cau_hinh_thong_tin_studio): JsonResponse
    {
        return $this->handleApi(function () use ($cau_hinh_thong_tin_studio) {
            $logo = $cau_hinh_thong_tin_studio->logo;
            $cau_hinh_thong_tin_studio->delete();

            if ($logo && Storage::disk('public')->exists($logo)) {
                Storage::disk('public')->delete($logo);
            }

            return response()->json(['message' => 'Đã xóa thông tin studio.']);

        }, 'xóa cấu hình studio');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatePayload(Request $request): array
    {
        return $request->validate([
            'ten_studio' => ['required', 'string', 'max:255'],
            'khau_hieu' => ['nullable', 'string', 'max:255'],
            'logo' => ['nullable', 'string', 'max:500'],
            'dia_chi' => ['nullable', 'string', 'max:500'],
            'email' => ['nullable', 'email', 'max:255'],
            'so_dien_thoai' => ['nullable', 'string', 'max:30'],
            'ma_so_thue' => ['nullable', 'string', 'max:50'],
            'mac_dinh' => ['required', 'string', Rule::in(['co', 'khong'])],
        ]);
    }
}
