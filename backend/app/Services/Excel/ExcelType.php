<?php

namespace App\Services\Excel;

interface ExcelType
{
    public function key(): string;

    public function label(): string;

    public function filename(): string;

    /**
     * @return array<string, array{header: string, aliases?: list<string>, required?: bool, max?: int}>
     */
    public function columns(): array;

    /**
     * Extra sheets (file mẫu / xuất). Rỗng nếu không cần.
     *
     * @return list<array{title: string, headers: array<string, string>, rows: list<array<string, mixed>>}>
     */
    public function extraSheets(): array;

    /**
     * @return list<array<string, mixed>>
     */
    public function exportRows(): array;

    /**
     * Nhập mảng JSON đã convert từ Excel. Chỉ thêm mới, không cập nhật.
     *
     * @param  list<array<string, mixed>>  $items
     * @return array{
     *   thanh_cong: list<array<string, mixed>>,
     *   that_bai: list<array{hang: int, mo_ta: string, du_lieu: array<string, mixed>}>
     * }
     */
    public function importItems(array $items): array;
}
