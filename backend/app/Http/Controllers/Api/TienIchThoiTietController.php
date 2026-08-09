<?php

namespace App\Http\Controllers\Api;

use App\Models\TienIchThoiTiet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TienIchThoiTietController extends BaseApiController
{
    /**
     * Danh sách thời tiết đã lưu.
     */
    public function index(Request $request): JsonResponse
    {
        return $this->handleApi(function () use ($request) {
            $validated = $request->validate([
                'page' => ['sometimes', 'integer', 'min:1'],
                'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
                'tu_ngay' => ['sometimes', 'nullable', 'date'],
                'den_ngay' => ['sometimes', 'nullable', 'date'],
            ]);

            $perPage = $validated['per_page'] ?? 20;

            $query = TienIchThoiTiet::query()
                ->when(! empty($validated['tu_ngay'] ?? null), function ($q) use ($validated) {
                    $q->whereDate('ngay', '>=', $validated['tu_ngay']);
                })
                ->when(! empty($validated['den_ngay'] ?? null), function ($q) use ($validated) {
                    $q->whereDate('ngay', '<=', $validated['den_ngay']);
                })
                ->orderBy('ngay');

            return response()->json($query->paginate($perPage));
        }, 'lấy danh sách thời tiết');
    }

    /**
     * Đồng bộ hàng loạt — trùng ngày thì ghi đè.
     *
     * Body: { items: [{ ngay, dia_diem?, mo_ta?, ty_le_mua?, toc_do_gio?, nhiet_do_min?, nhiet_do_max?, icon?, icon_code? }] }
     */
    public function sync(Request $request): JsonResponse
    {
        return $this->handleApi(function () use ($request) {
            $validated = $request->validate([
                'items' => ['required', 'array', 'min:1'],
                'items.*.ngay' => ['required', 'date'],
                'items.*.dia_diem' => ['nullable', 'string', 'max:255'],
                'items.*.mo_ta' => ['nullable', 'string', 'max:255'],
                'items.*.ty_le_mua' => ['nullable', 'integer', 'min:0', 'max:100'],
                'items.*.toc_do_gio' => ['nullable', 'numeric', 'min:0'],
                'items.*.nhiet_do_min' => ['nullable', 'integer', 'min:-50', 'max:60'],
                'items.*.nhiet_do_max' => ['nullable', 'integer', 'min:-50', 'max:60'],
                'items.*.icon' => ['nullable', 'string', 'max:255'],
                'items.*.icon_code' => ['nullable', 'string', 'max:16'],
            ]);

            $created = 0;
            $updated = 0;
            $rows = [];

            DB::transaction(function () use ($validated, &$created, &$updated, &$rows) {
                foreach ($validated['items'] as $item) {
                    $record = TienIchThoiTiet::updateOrCreate(
                        ['ngay' => $item['ngay']],
                        [
                            'dia_diem' => $item['dia_diem'] ?? null,
                            'mo_ta' => $item['mo_ta'] ?? null,
                            'ty_le_mua' => $item['ty_le_mua'] ?? 0,
                            'toc_do_gio' => $item['toc_do_gio'] ?? null,
                            'nhiet_do_min' => $item['nhiet_do_min'] ?? null,
                            'nhiet_do_max' => $item['nhiet_do_max'] ?? null,
                            'icon' => $item['icon'] ?? null,
                            'icon_code' => $item['icon_code'] ?? null,
                        ]
                    );

                    if ($record->wasRecentlyCreated) {
                        $created++;
                    } else {
                        $updated++;
                    }

                    $rows[] = $record;
                }
            });

            return response()->json([
                'message' => "Đã đồng bộ dữ liệu thời tiết thành công.",
                'created' => $created,
                'updated' => $updated,
                'data' => $rows,
            ]);
        }, 'đồng bộ thời tiết');
    }
}
