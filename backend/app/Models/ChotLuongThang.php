<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'thang',
    'nam',
    'nguoi_chot_id',
    'du_lieu_chot',
    'trang_thai',
])]
class ChotLuongThang extends Model
{
    public const TRANG_THAI_CHUA_CHOT = 'chua_chot';

    public const TRANG_THAI_DA_CHOT = 'da_chot';

    public const THANH_TOAN_CHUA = 'chua_thanh_toan';

    public const THANH_TOAN_DA = 'da_thanh_toan';

    protected $table = 'chot_luong_thang';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'thang' => 'integer',
            'nam' => 'integer',
            'du_lieu_chot' => 'array',
        ];
    }

    public function nguoiChot(): BelongsTo
    {
        return $this->belongsTo(User::class, 'nguoi_chot_id');
    }
}
