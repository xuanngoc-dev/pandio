<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'ngay',
    'cpqc_tiktok',
    'cpqc_fb',
    'cpqc_google',
    'inbox_tiktok',
    'cpi_tiktok',
    'inbox_fb',
    'cpi_fb',
    'kh_tiktok',
    'kh_fb',
    'kh_google',
    'tcpl_tiktok',
    'cpl_fb',
    'cpl_google',
    'lich_hen',
    'khach_den_tu_hen',
    'ghi_chu',
])]
class ReportQuangCao extends Model
{
    protected $table = 'report_quang_cao';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'ngay' => 'date:Y-m-d',
            'cpqc_tiktok' => 'integer',
            'cpqc_fb' => 'integer',
            'cpqc_google' => 'integer',
            'inbox_tiktok' => 'integer',
            'cpi_tiktok' => 'integer',
            'inbox_fb' => 'integer',
            'cpi_fb' => 'integer',
            'kh_tiktok' => 'integer',
            'kh_fb' => 'integer',
            'kh_google' => 'integer',
            'tcpl_tiktok' => 'integer',
            'cpl_fb' => 'integer',
            'cpl_google' => 'integer',
            'lich_hen' => 'integer',
            'khach_den_tu_hen' => 'integer',
        ];
    }
}
