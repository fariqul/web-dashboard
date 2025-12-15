<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CCTransaction extends Model
{
    protected $table = 'cc_transactions';
    
    protected $fillable = [
        'transaction_number',
        'booking_id',
        'employee_name',
        'personel_number',
        'trip_number',
        'origin',
        'destination',
        'trip_destination_full',
        'departure_date',
        'return_date',
        'duration_days',
        'payment_amount',
        'status',
        'transaction_type',
        'sheet',
    ];
}
