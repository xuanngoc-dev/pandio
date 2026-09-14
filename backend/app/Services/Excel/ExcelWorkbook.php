<?php

namespace App\Services\Excel;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ExcelWorkbook
{
    /**
     * @param  array<string, string>  $headers
     * @param  list<array<string, mixed>>  $rows
     * @param  list<array{title: string, headers: array<string, string>, rows: list<array<string, mixed>>}>  $extraSheets
     */
    public function write(string $title, array $headers, array $rows, array $extraSheets = []): string
    {
        $spreadsheet = new Spreadsheet;
        $this->fillSheet($spreadsheet->getActiveSheet(), $title, $headers, $rows);

        foreach ($extraSheets as $sheet) {
            $this->fillSheet(
                $spreadsheet->createSheet(),
                (string) ($sheet['title'] ?? 'Sheet'),
                is_array($sheet['headers'] ?? null) ? $sheet['headers'] : [],
                is_array($sheet['rows'] ?? null) ? $sheet['rows'] : []
            );
        }

        $spreadsheet->setActiveSheetIndex(0);

        $path = tempnam(sys_get_temp_dir(), 'pandio-xlsx-') ?: sys_get_temp_dir().'/pandio-xlsx-'.uniqid();
        $xlsxPath = $path.'.xlsx';
        @unlink($path);

        $writer = new Xlsx($spreadsheet);
        $writer->save($xlsxPath);
        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet, $writer);

        return $xlsxPath;
    }

    /**
     * @param  array<string, string>  $headers
     * @param  list<array<string, mixed>>  $rows
     */
    private function fillSheet(Worksheet $sheet, string $title, array $headers, array $rows): void
    {
        $sheet->setTitle($this->safeSheetTitle($title));

        $fields = array_keys($headers);
        $columnCount = max(1, count($fields));

        foreach (array_values($headers) as $index => $label) {
            $sheet->setCellValue([$index + 1, 1], $label);
        }

        foreach ($rows as $rowIndex => $row) {
            foreach ($fields as $colIndex => $field) {
                $sheet->setCellValue([$colIndex + 1, $rowIndex + 2], $row[$field] ?? '');
            }
        }

        $this->styleHeader($sheet, $columnCount);
        $this->autoSizeColumns($sheet, $columnCount);
        $sheet->freezePane('A2');
        $sheet->setAutoFilter([1, 1, $columnCount, max(1, count($rows) + 1)]);
    }

    /**
     * @return list<list<mixed>>
     */
    public function read(string $path): array
    {
        $spreadsheet = IOFactory::load($path);
        $sheet = $spreadsheet->getActiveSheet();
        $table = $sheet->toArray(null, true, true, false);
        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);

        return array_values(array_map(
            static fn (array $row) => array_values($row),
            $table
        ));
    }

    /**
     * Hàng tiêu đề: trim + bỏ cột trống ở cuối.
     *
     * @param  list<list<mixed>>  $table
     * @return list<string>
     */
    public function headerRow(array $table): array
    {
        $row = $table[0] ?? [];
        $headers = [];
        foreach ($row as $cell) {
            $headers[] = trim((string) ($cell ?? ''));
        }

        while ($headers !== [] && end($headers) === '') {
            array_pop($headers);
        }

        return array_values($headers);
    }

    /**
     * So khớp đúng thứ tự và tên cột với file mẫu.
     *
     * @param  list<string>  $actual
     * @param  list<string>  $expected
     */
    public function headersMatch(array $actual, array $expected): bool
    {
        if (count($actual) !== count($expected)) {
            return false;
        }

        foreach ($expected as $index => $header) {
            if (trim((string) $actual[$index]) !== trim((string) $header)) {
                return false;
            }
        }

        return true;
    }

    private function styleHeader(Worksheet $sheet, int $columnCount): void
    {
        $range = 'A1:'.Coordinate::stringFromColumnIndex($columnCount).'1';
        $sheet->getStyle($range)->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '409EFF'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'DCDFE6'],
                ],
            ],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(22);
    }

    private function autoSizeColumns(Worksheet $sheet, int $columnCount): void
    {
        for ($index = 1; $index <= $columnCount; $index++) {
            $sheet->getColumnDimensionByColumn($index)->setAutoSize(true);
        }
    }

    private function safeSheetTitle(string $title): string
    {
        $clean = trim(preg_replace('/[\\\\\\/\\*\\?\\:\\[\\]]/', '', $title) ?? $title);

        if ($clean === '') {
            return 'Du lieu';
        }

        return mb_substr($clean, 0, 31);
    }
}
