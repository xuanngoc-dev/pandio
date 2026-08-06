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

            // Tổng toàn bộ kết quả lọc (không phụ thuộc phân trang)
            $summary = (clone $query)
                ->reorder()
                ->toBase()
                ->selectRaw(implode(', ', [
                    'COALESCE(SUM(cpqc_tiktok), 0) as cpqc_tiktok',
                    'COALESCE(SUM(cpqc_fb), 0) as cpqc_fb',
                    'COALESCE(SUM(cpqc_google), 0) as cpqc_google',
                    'COALESCE(SUM(inbox_tiktok), 0) as inbox_tiktok',
                    'COALESCE(SUM(cpi_tiktok), 0) as cpi_tiktok',
                    'COALESCE(SUM(inbox_fb), 0) as inbox_fb',
                    'COALESCE(SUM(cpi_fb), 0) as cpi_fb',
                    'COALESCE(SUM(kh_tiktok), 0) as kh_tiktok',
                    'COALESCE(SUM(kh_fb), 0) as kh_fb',
                    'COALESCE(SUM(kh_google), 0) as kh_google',
                    'COALESCE(SUM(tcpl_tiktok), 0) as tcpl_tiktok',
                    'COALESCE(SUM(cpl_fb), 0) as cpl_fb',
                    'COALESCE(SUM(cpl_google), 0) as cpl_google',
                    'COALESCE(SUM(lich_hen), 0) as lich_hen',
                    'COALESCE(SUM(khach_den_tu_hen), 0) as khach_den_tu_hen',
                ]))
                ->first();

            $paginator = $query->paginate($perPage);
            $payload = $paginator->toArray();
            $payload['summary'] = $summary
                ? array_map('intval', (array) $summary)
                : array_fill_keys([
                    'cpqc_tiktok', 'cpqc_fb', 'cpqc_google',
                    'inbox_tiktok', 'cpi_tiktok', 'inbox_fb', 'cpi_fb',
                    'kh_tiktok', 'kh_fb', 'kh_google',
                    'tcpl_tiktok', 'cpl_fb', 'cpl_google',
                    'lich_hen', 'khach_den_tu_hen',
                ], 0);

            return response()->json($payload);

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
