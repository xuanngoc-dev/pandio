<?php

namespace App\Services\Excel;

use App\Services\Excel\Types\DanhMucTrangPhucExcelType;
use Illuminate\Validation\ValidationException;

class ExcelTypeRegistry
{
    /** @var array<string, class-string<ExcelType>> */
    private const TYPES = [
        'danh_muc_trang_phuc' => DanhMucTrangPhucExcelType::class,
    ];

    /**
     * @return list<string>
     */
    public function keys(): array
    {
        return array_keys(self::TYPES);
    }

    public function resolve(string $loai): ExcelType
    {
        $class = self::TYPES[$loai] ?? null;
        if ($class === null) {
            throw ValidationException::withMessages([
                'loai' => 'Loại dữ liệu không được hỗ trợ.',
            ]);
        }

        return app($class);
    }
}
