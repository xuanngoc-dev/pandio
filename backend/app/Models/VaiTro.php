<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'ten_vai_tro',
    'ghi_chu',
    'danh_sach_menu',
    'cau_hinh',
])]
class VaiTro extends Model
{
    protected $table = 'vai_tro';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'danh_sach_menu' => 'array',
            'cau_hinh' => 'array',
        ];
    }
}
