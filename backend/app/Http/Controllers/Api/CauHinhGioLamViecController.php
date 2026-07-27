<?php

namespace App\Http\Controllers\Api;

use App\Models\CauHinhGioLamViec;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CauHinhGioLamViecController extends BaseApiController
{
    /**
     * Danh sách giờ làm việc — phân trang + tìm kiếm.
     *
     * Query: page, per_page, keyword, su_dung
     */
    public function index(Request $request): JsonResponse
    {
        return $this->handleApi(function () use ($request) {
            $validated = $request->validate([
                'page' => ['sometimes', 'integer', 'min:1'],
                'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
                'keyword' => ['sometimes', 'nullable', 'string', 'max:255'],
                'su_dung' => ['sometimes', 'nullable', 'string', Rule::in(['co', 'khong'])],
            ]);

            $perPage = $validated['per_page'] ?? 10;
            $keyword = trim((string) ($validated['keyword'] ?? ''));
            $suDung = $validated['su_dung'] ?? null;

            $query = CauHinhGioLamViec::query()
                ->when($keyword !== '', function ($q) use ($keyword) {
                    $q->where('ten_cau_hinh', 'like', "%{$keyword}%");
                })
                ->when($suDung, fn ($q) => $q->where('su_dung', $suDung))
                ->orderByRaw("CASE WHEN su_dung = 'co' THEN 0 ELSE 1 END")
                ->orderByDesc('id');

            return response()->json($query->paginate($perPage));

        }, 'lấy danh sách giờ làm việc');
    }

    /**
     * Chi tiết một cấu hình giờ làm việc.
     */
    public function show(CauHinhGioLamViec $cau_hinh_gio_lam_viec): JsonResponse
    {
        return $this->handleApi(function () use ($cau_hinh_gio_lam_viec) {
            return response()->json($cau_hinh_gio_lam_viec);

        }, 'lấy chi tiết giờ làm việc');
    }

    /**
     * Tạo cấu hình giờ làm việc mới.
     * Luôn đảm bảo đúng 1 bản ghi có su_dung = co khi đã có dữ liệu.
     */
    public function store(Request $request): JsonResponse
    {
        return $this->handleApi(function () use ($request) {
            $validated = $this->validatePayload($request);

            $item = DB::transaction(function () use ($validated) {
                $hasAny = CauHinhGioLamViec::query()->exists();
                $hasActive = CauHinhGioLamViec::query()->where('su_dung', 'co')->exists();

                // Bản ghi đầu tiên, hoặc chưa có cấu hình đang dùng, hoặc chọn sử dụng → đặt làm đang dùng
                if (! $hasAny || ! $hasActive || ($validated['su_dung'] ?? 'khong') === 'co') {
                    CauHinhGioLamViec::query()->update(['su_dung' => 'khong']);
                    $validated['su_dung'] = 'co';
                }

                return CauHinhGioLamViec::create($validated);
            });

            return response()->json($item, 201);

        }, 'tạo giờ làm việc');
    }

    /**
     * Cập nhật cấu hình giờ làm việc.
     */
    public function update(Request $request, CauHinhGioLamViec $cau_hinh_gio_lam_viec): JsonResponse
    {
        return $this->handleApi(function () use ($request, $cau_hinh_gio_lam_viec) {
            $validated = $this->validatePayload($request);

            $item = DB::transaction(function () use ($cau_hinh_gio_lam_viec, $validated) {
                $wantUse = ($validated['su_dung'] ?? 'khong') === 'co';
                $isCurrentlyUsed = $cau_hinh_gio_lam_viec->su_dung === 'co';

                if ($isCurrentlyUsed && ! $wantUse) {
                    throw ValidationException::withMessages([
                        'su_dung' => [
                            'Phải có đúng 1 cấu hình giờ làm đang được sử dụng. Hãy chọn cấu hình khác trước khi bỏ sử dụng.',
                        ],
                    ]);
                }

                if ($wantUse) {
                    CauHinhGioLamViec::query()
                        ->where('id', '!=', $cau_hinh_gio_lam_viec->id)
                        ->update(['su_dung' => 'khong']);
                    $validated['su_dung'] = 'co';
                }

                $cau_hinh_gio_lam_viec->update($validated);

                return $cau_hinh_gio_lam_viec->fresh();
            });

            return response()->json($item);

        }, 'cập nhật giờ làm việc');
    }

    /**
     * Xóa cấu hình giờ làm việc.
     * Không cho xóa bản ghi đang sử dụng nếu còn cấu hình khác.
     */
    public function destroy(CauHinhGioLamViec $cau_hinh_gio_lam_viec): JsonResponse
    {
        return $this->handleApi(function () use ($cau_hinh_gio_lam_viec) {
            if ($cau_hinh_gio_lam_viec->su_dung === 'co') {
                $hasOthers = CauHinhGioLamViec::query()
                    ->where('id', '!=', $cau_hinh_gio_lam_viec->id)
                    ->exists();

                if ($hasOthers) {
                    throw ValidationException::withMessages([
                        'su_dung' => [
                            'Không thể xóa cấu hình đang được sử dụng. Hãy chọn cấu hình khác trước.',
                        ],
                    ]);
                }
            }

            $cau_hinh_gio_lam_viec->delete();

            return response()->json(['message' => 'Đã xóa cấu hình giờ làm việc.']);

        }, 'xóa giờ làm việc');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatePayload(Request $request): array
    {
        return $request->validate([
            'ten_cau_hinh' => ['required', 'string', 'max:255'],
            'gio_vao_buoi_sang' => ['required', 'date_format:H:i'],
            'gio_tan_buoi_sang' => ['required', 'date_format:H:i', 'after:gio_vao_buoi_sang'],
            'gio_vao_buoi_chieu' => ['required', 'date_format:H:i', 'after:gio_tan_buoi_sang'],
            'gio_tan_buoi_chieu' => ['required', 'date_format:H:i', 'after:gio_vao_buoi_chieu'],
            'su_dung' => ['required', 'string', Rule::in(['co', 'khong'])],
        ]);
    }
}
