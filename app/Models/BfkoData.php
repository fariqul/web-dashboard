<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BfkoData extends Model
{
    protected $table = 'bfko_data';
    
    protected $fillable = [
        'nip',
        'nama',
        'jabatan',
        'unit',
        'bulan',
        'tahun',
        'nilai_angsuran',
        'tanggal_bayar',
        'status_angsuran',
        'keterangan'
    ];
    
    protected $casts = [
        'nilai_angsuran' => 'decimal:2',
        'tanggal_bayar' => 'date',
        'tahun' => 'integer'
    ];
    
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
    
    /**
     * Scope untuk filter by NIP
     */
    public function scopeByNip($query, $nip)
    {
        return $query->where('nip', $nip);
    }
}
