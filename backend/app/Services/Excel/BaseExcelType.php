<?php

namespace App\Services\Excel;

abstract class BaseExcelType implements ExcelType
{
    /**
     * @return array<string, string>
     */
    public function headers(): array
    {
        $headers = [];
        foreach ($this->columns() as $field => $column) {
            $headers[$field] = $column['header'];
        }

        return $headers;
    }

    public function downloadName(): string
    {
        return $this->filename().'-'.now()->format('Ymd-His').'.xlsx';
    }

    public function templateName(): string
    {
        return $this->filename().'-mau.xlsx';
    }

    /**
     * Convert bảng Excel (hàng tiêu đề + dữ liệu) thành mảng JSON.
     *
     * @param  list<list<mixed>>  $table
     * @return list<array<string, mixed>>
     */
    public function toJsonItems(array $table): array
    {
        if ($table === []) {
            return [];
        }

        $headerRow = array_shift($table);
        $indexToField = $this->mapHeaderIndexes(is_array($headerRow) ? $headerRow : []);

        $items = [];
        foreach ($table as $offset => $rawRow) {
            $item = ['hang' => $offset + 2];

            foreach ($indexToField as $index => $field) {
                $item[$field] = $this->stringifyCell($rawRow[$index] ?? null);
            }

            $items[] = $item;
        }

        return $items;
    }

    /**
     * JSON xem trước: bỏ dòng trống.
     *
     * @param  list<list<mixed>>  $table
     * @return list<array<string, mixed>>
     */
    public function previewItems(array $table): array
    {
        $items = [];
        foreach ($this->toJsonItems($table) as $item) {
            if ($this->isEmptyRow($this->itemValues($item))) {
                continue;
            }
            $items[] = $item;
        }

        return $items;
    }

    /**
     * @return list<array{key: string, label: string}>
     */
    public function previewColumns(): array
    {
        $columns = [
            ['key' => 'hang', 'label' => 'Hàng'],
        ];

        foreach ($this->headers() as $key => $label) {
            $columns[] = ['key' => $key, 'label' => $label];
        }

        return $columns;
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    protected function itemValues(array $item): array
    {
        $values = [];
        foreach (array_keys($this->columns()) as $field) {
            $values[$field] = $this->stringifyCell($item[$field] ?? null);
        }

        return $values;
    }

    /**
     * @param  list<mixed>  $headerRow
     * @return array<int, string>
     */
    protected function mapHeaderIndexes(array $headerRow): array
    {
        $lookup = [];
        foreach ($this->columns() as $field => $column) {
            $lookup[$this->normalizeHeader($field)] = $field;
            $lookup[$this->normalizeHeader((string) $column['header'])] = $field;
            foreach ($column['aliases'] ?? [] as $alias) {
                $lookup[$this->normalizeHeader((string) $alias)] = $field;
            }
        }

        $mapped = [];
        foreach ($headerRow as $index => $header) {
            $normalized = $this->normalizeHeader((string) $header);
            if ($normalized === '' || ! isset($lookup[$normalized])) {
                continue;
            }

            $mapped[(int) $index] = $lookup[$normalized];
        }

        return $mapped;
    }

    protected function normalizeHeader(string $value): string
    {
        $value = trim(mb_strtolower($value));

        return preg_replace('/\s+/u', ' ', $value) ?? $value;
    }

    protected function stringifyCell(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_float($value) && floor($value) === $value) {
            return (string) (int) $value;
        }

        return trim((string) $value);
    }

    /**
     * @param  array<string, mixed>  $values
     */
    protected function isEmptyRow(array $values): bool
    {
        foreach ($values as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }
}
