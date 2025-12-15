<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BfkoPayment extends Model
{
    protected $table = 'bfko_payments';
    
    protected $fillable = [
        'nip',
        'bulan',
        'tahun',
        'nilai_angsuran',
        'tanggal_pembayaran'
    ];
    
    protected $casts = [
        'nilai_angsuran' => 'decimal:2',
        'tanggal_pembayaran' => 'date'
    ];
    
    /**
     * Get the employee for this payment
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(BfkoEmployee::class, 'nip', 'nip');
    }
    
    /**
     * Scope untuk filter by bulan
     */
    public function scopeByBulan($query, $bulan)
    {
        return $query->where('bulan', $bulan);
    }
    
    /**
     * Scope untuk filter by tahun
     */
    public function scopeByTahun($query, $tahun)
    {
        return $query->where('tahun', $tahun);
    }
    
    /**
     * Scope untuk filter by periode (bulan + tahun)
     */
    public function scopeByPeriode($query, $bulan, $tahun)
    {
        return $query->where('bulan', $bulan)->where('tahun', $tahun);
    }
}
