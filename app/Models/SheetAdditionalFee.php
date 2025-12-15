<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SheetAdditionalFee extends Model
{
    protected $table = 'sheet_additional_fees';
    
    protected $fillable = [
        'sheet_name',
        'biaya_adm_bunga',
        'biaya_transfer',
        'iuran_tahunan',
    ];
}
