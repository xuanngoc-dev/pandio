<?php

namespace App\Http\Controllers\Api;

use App\Models\ReportQuangCao;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportQuangCaoController extends BaseApiController
{
    /**
     * Danh sách report quảng cáo — phân trang + lọc theo ngày / ghi chú.
     *
     * Query: page, per_page, keyword, ngay_tu, ngay_den
     */
    public function index(Request $request): JsonResponse
    {
        return $this->handleApi(function () use ($request) {
            $validated = $request->validate([
                'page' => ['sometimes', 'integer', 'min:1'],
                'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
                'keyword' => ['sometimes', 'nullable', 'string', 'max:255'],
                'ngay_tu' => ['sometimes', 'nullable', 'date'],
                'ngay_den' => ['sometimes', 'nullable', 'date'],
            ]);

            $perPage = $validated['per_page'] ?? 10;
            $keyword = trim((string) ($validated['keyword'] ?? ''));

            $query = ReportQuangCao::query()
                ->when($keyword !== '', function ($q) use ($keyword) {
                    $q->where('ghi_chu', 'like', "%{$keyword}%");
                })
                ->when(! empty($validated['ngay_tu']), function ($q) use ($validated) {
                    $q->whereDate('ngay', '>=', $validated['ngay_tu']);
                })
                ->when(! empty($validated['ngay_den']), function ($q) use ($validated) {
                    $q->whereDate('ngay', '<=', $validated['ngay_den']);
                })
                ->orderByDesc('ngay')
                ->orderByDesc('id');

            return response()->json($query->paginate($perPage));

        }, 'lấy danh sách report quảng cáo');
    }

    /**
     * Chi tiết một report quảng cáo.
     */
    public function show(ReportQuangCao $report_quang_cao): JsonResponse
    {
        return $this->handleApi(function () use ($report_quang_cao) {
            return response()->json($report_quang_cao);

        }, 'lấy chi tiết report quảng cáo');
    }

    /**
     * Tạo report quảng cáo mới.
     */
    public function store(Request $request): JsonResponse
    {
        return $this->handleApi(function () use ($request) {
            $validated = $request->validate($this->rules());

            $report = ReportQuangCao::create($validated);

            return response()->json($report, 201);

        }, 'tạo report quảng cáo');
    }

    /**
     * Cập nhật report quảng cáo.
     */
    public function update(Request $request, ReportQuangCao $report_quang_cao): JsonResponse
    {
        return $this->handleApi(function () use ($request, $report_quang_cao) {
            $validated = $request->validate($this->rules());

            $report_quang_cao->update($validated);

            return response()->json($report_quang_cao->fresh());

        }, 'cập nhật report quảng cáo');
    }

    /**
     * Xóa report quảng cáo.
     */
    public function destroy(ReportQuangCao $report_quang_cao): JsonResponse
    {
        return $this->handleApi(function () use ($report_quang_cao) {
            $report_quang_cao->delete();

            return response()->json(['message' => 'Đã xóa report quảng cáo.']);

        }, 'xóa report quảng cáo');
    }

    /**
     * @return array<string, list<string>>
     */
    private function rules(): array
    {
        return [
            'ngay' => ['required', 'date'],
            'cpqc_tiktok' => ['nullable', 'numeric', 'min:0'],
            'cpqc_fb' => ['nullable', 'numeric', 'min:0'],
            'cpqc_google' => ['nullable', 'numeric', 'min:0'],
            'inbox_tiktok' => ['nullable', 'integer', 'min:0'],
            'cpi_tiktok' => ['nullable', 'numeric', 'min:0'],
            'inbox_fb' => ['nullable', 'integer', 'min:0'],
            'cpi_fb' => ['nullable', 'numeric', 'min:0'],
            'kh_tiktok' => ['nullable', 'integer', 'min:0'],
            'kh_fb' => ['nullable', 'integer', 'min:0'],
            'kh_google' => ['nullable', 'integer', 'min:0'],
            'tcpl_tiktok' => ['nullable', 'numeric', 'min:0'],
            'cpl_fb' => ['nullable', 'numeric', 'min:0'],
            'cpl_google' => ['nullable', 'numeric', 'min:0'],
            'lich_hen' => ['nullable', 'integer', 'min:0'],
            'khach_den_tu_hen' => ['nullable', 'integer', 'min:0'],
            'ghi_chu' => ['nullable', 'string'],
        ];
    }
}
