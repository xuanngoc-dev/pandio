<?php

namespace App\Http\Controllers\Api;

use App\Models\CauHinhJson;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CauHinhJsonController extends BaseApiController
{
    /**
     * Lấy cấu hình JSON hiện tại (tạo mới nếu chưa có).
     */
    public function show(): JsonResponse
    {
        return $this->handleApi(function () {
            $item = CauHinhJson::query()->firstOrCreate(
                [],
                ['thong_tin_cau_hinh' => []]
            );

            return response()->json($item);

        }, 'lấy chi tiết cấu hình JSON');
    }

    /**
     * Cập nhật / tạo cấu hình JSON (upsert singleton, merge theo key).
     */
    public function update(Request $request): JsonResponse
    {
        return $this->handleApi(function () use ($request) {
            $validated = $request->validate([
                'thong_tin_cau_hinh' => ['required', 'array'],
            ]);

            $item = CauHinhJson::query()->firstOrCreate(
                [],
                ['thong_tin_cau_hinh' => []]
            );

            $merged = array_merge(
                $item->thong_tin_cau_hinh ?? [],
                $validated['thong_tin_cau_hinh']
            );

            $item->update(['thong_tin_cau_hinh' => $merged]);

            return response()->json($item->fresh());

        }, 'cập nhật cấu hình JSON');
    }
}
