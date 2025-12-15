<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BfkoEmployee extends Model
{
    protected $table = 'bfko_employees';
    
    protected $fillable = [
        'nip',
        'nama_pegawai',
        'jabatan',
        'jenjang_jabatan',
        'unit',
        'status_angsuran',
        'sisa_angsuran'
    ];
    
    protected $casts = [
        'sisa_angsuran' => 'decimal:2'
    ];
    
    /**
     * Get all payments for this employee
     */
    public function payments(): HasMany
    {
        return $this->hasMany(BfkoPayment::class, 'nip', 'nip');
    }
    
    /**
     * Get total payments
     */
    public function getTotalPaymentsAttribute()
    {
        return $this->payments->sum('nilai_angsuran');
    }
}
