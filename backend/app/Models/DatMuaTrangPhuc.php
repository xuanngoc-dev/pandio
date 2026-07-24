<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'nha_cung_cap_id',
    'loai_don_hang',
    'nguon_hang_hoa',
    'ngay_dat',
    'mat_hang',
    'tong_tien_hang',
    'phi_van_chuyen',
    'tien_coc',
    'du_no',
])]
class DatMuaTrangPhuc extends Model
{
    protected $table = 'dat_mua_trang_phuc';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'ngay_dat' => 'date',
            'mat_hang' => 'array',
            'tong_tien_hang' => 'integer',
            'phi_van_chuyen' => 'integer',
            'tien_coc' => 'integer',
            'du_no' => 'integer',
        ];
    }

    public function nhaCungCap(): BelongsTo
    {
        return $this->belongsTo(NhaCungCapTrangPhuc::class, 'nha_cung_cap_id');
    }
}
