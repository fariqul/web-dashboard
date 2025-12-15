<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SppdTransaction extends Model
{
    protected $table = 'sppd_transactions';
    
    protected $fillable = [
        'transaction_number',
        'trip_number',
        'customer_name',
        'origin',
        'destination',
        'trip_destination_full',
        'reason_for_trip',
        'trip_begins_on',
        'trip_ends_on',
        'planned_payment_date',
        'duration_days',
        'paid_amount',
        'beneficiary_bank_name',
        'status',
        'sheet',
    ];

    protected $casts = [
        'trip_begins_on' => 'date',
        'trip_ends_on' => 'date',
        'planned_payment_date' => 'date',
        'paid_amount' => 'decimal:2',
    ];

    /**
     * Scope untuk search
     */
    public function scopeSearch($query, $search)
    {
        if (empty($search)) {
            return $query;
        }

        return $query->where(function($q) use ($search) {
            $q->where('customer_name', 'like', '%' . $search . '%')
              ->orWhere('trip_number', 'like', '%' . $search . '%')
              ->orWhere('trip_destination_full', 'like', '%' . $search . '%')
              ->orWhere('origin', 'like', '%' . $search . '%')
              ->orWhere('destination', 'like', '%' . $search . '%')
              ->orWhere('reason_for_trip', 'like', '%' . $search . '%')
              ->orWhere('beneficiary_bank_name', 'like', '%' . $search . '%');
        });
    }
}
