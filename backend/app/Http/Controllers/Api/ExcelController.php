<?php

namespace App\Http\Controllers\Api;

use App\Services\Excel\BaseExcelType;
use App\Services\Excel\ExcelTypeRegistry;
use App\Services\Excel\ExcelWorkbook;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExcelController extends BaseApiController
{
    public function __construct(
        private readonly ExcelTypeRegistry $registry,
        private readonly ExcelWorkbook $workbook,
    ) {}

    /**
     * Xuất toàn bộ dữ liệu theo loại ra file Excel.
     *
     * Query: loai
     */
    public function export(Request $request): BinaryFileResponse
    {
        $type = $this->resolveType($request);

        $path = $this->workbook->write(
            $type->label(),
            $type->headers(),
            $type->exportRows(),
            $type->extraSheets()
        );

        return $this->downloadXlsx($path, $type->downloadName());
    }

    /**
     * Tải file Excel mẫu (chỉ có hàng tiêu đề).
     *
     * Query: loai
     */
    public function template(Request $request): BinaryFileResponse
    {
        $type = $this->resolveType($request);

        $path = $this->workbook->write(
            $type->label(),
            $type->headers(),
            [],
            $type->extraSheets()
        );

        return $this->downloadXlsx($path, $type->templateName());
    }

    /**
     * Nhập dữ liệu: convert Excel → JSON rồi thêm theo mảng.
     * Chỉ tạo mới khi mã chưa tồn tại. Trùng mã / lỗi dòng đưa vào that_bai.
     *
     * Body: loai + items (JSON đã xem trước) hoặc file
     */
    public function import(Request $request): JsonResponse
    {
        return $this->handleApi(function () use ($request) {
            $type = $this->resolveType($request);
            $items = $this->resolveImportItems($request, $type);

            $result = $type->importItems($items);
            $thanhCong = $result['thanh_cong'] ?? [];
            $thatBai = $result['that_bai'] ?? [];

            if ($thanhCong === [] && $thatBai === []) {
                throw ValidationException::withMessages([
                    'items' => 'Không có dữ liệu để nhập.',
                ]);
            }

            $tong = count($thanhCong) + count($thatBai);

            return response()->json([
                'message' => $this->importMessage(count($thanhCong), $tong),
                'thanh_cong' => $thanhCong,
                'that_bai' => $thatBai,
            ]);
        }, 'nhập excel');
    }

    /**
     * Kiểm tra file có đúng hàng tiêu đề như file mẫu không.
     *
     * Body: loai, file
     */
    public function validateTemplate(Request $request): JsonResponse
    {
        return $this->handleApi(function () use ($request) {
            $type = $this->resolveType($request);
            $this->validateExcelFile($request);

            $table = $this->workbook->read($request->file('file')->getRealPath());
            $expected = array_values($type->headers());
            $actual = $this->workbook->headerRow($table);
            $hopLe = $this->workbook->headersMatch($actual, $expected);
            $items = $hopLe ? $type->previewItems($table) : [];

            return response()->json([
                'hop_le' => $hopLe,
                'message' => $hopLe
                    ? 'File đúng định dạng file mẫu.'
                    : 'File không đúng định dạng file mẫu. Vui lòng tải file mẫu và chọn lại.',
                'cot_mau' => $expected,
                'cot_file' => $actual,
                'cot' => $type->previewColumns(),
                'items' => $items,
            ]);
        }, 'kiểm tra file excel');
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function resolveImportItems(Request $request, BaseExcelType $type): array
    {
        if ($request->exists('items')) {
            $validated = $request->validate([
                'items' => ['required', 'array', 'min:1'],
                'items.*' => ['required', 'array'],
            ], [
                'items.required' => 'Không có dữ liệu để nhập.',
                'items.min' => 'Không có dữ liệu để nhập.',
            ]);

            return array_values($validated['items']);
        }

        $this->validateExcelFile($request);

        $table = $this->workbook->read($request->file('file')->getRealPath());
        $this->assertTemplateHeaders($type, $table);
        $items = $type->previewItems($table);

        if ($items === []) {
            throw ValidationException::withMessages([
                'file' => 'File không có dữ liệu để nhập.',
            ]);
        }

        return $items;
    }

    private function validateExcelFile(Request $request): void
    {
        $request->validate([
            'file' => ['required', 'file', 'extensions:xlsx,xls,csv', 'max:5120'],
        ], [
            'file.required' => 'Vui lòng chọn file Excel.',
            'file.file' => 'File tải lên không hợp lệ.',
            'file.extensions' => 'Chỉ chấp nhận file .xlsx, .xls hoặc .csv.',
            'file.max' => 'File tối đa 5MB.',
        ]);
    }

    private function assertTemplateHeaders(BaseExcelType $type, array $table): void
    {
        $expected = array_values($type->headers());
        $actual = $this->workbook->headerRow($table);

        if (! $this->workbook->headersMatch($actual, $expected)) {
            throw ValidationException::withMessages([
                'file' => 'File không đúng định dạng file mẫu. Vui lòng tải file mẫu và chọn lại.',
            ]);
        }
    }

    private function resolveType(Request $request): BaseExcelType
    {
        $validated = $request->validate([
            'loai' => ['required', 'string', Rule::in($this->registry->keys())],
        ], [
            'loai.required' => 'Vui lòng chọn loại dữ liệu.',
            'loai.in' => 'Loại dữ liệu không được hỗ trợ.',
        ]);

        $type = $this->registry->resolve($validated['loai']);

        if (! $type instanceof BaseExcelType) {
            throw ValidationException::withMessages([
                'loai' => 'Loại dữ liệu không được hỗ trợ.',
            ]);
        }

        return $type;
    }

    private function downloadXlsx(string $path, string $filename): BinaryFileResponse
    {
        return response()
            ->download($path, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])
            ->deleteFileAfterSend(true);
    }

    private function importMessage(int $thanhCong, int $tong): string
    {
        return "Import thành công {$thanhCong}/{$tong} bản ghi.";
    }
}
