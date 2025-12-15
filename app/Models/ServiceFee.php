<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceFee extends Model
{
    protected $fillable = [
        'booking_id',
        'merchant',
        'transaction_time',
        'status',
        'transaction_amount',
        'base_amount',
        'service_fee',
        'vat',
        'total_tagihan',
        'service_type',
        'sheet',
        'description',
        'hotel_name',
        'room_type',
        'route',
        'trip_type',
        'pax',
        'airline_id',
        'booker_email',
        'employee_name',
    ];

    /**
     * Search scope for filtering by various fields
     */
    public function scopeSearch($query, $search)
    {
        if (empty($search)) {
            return $query;
        }

        return $query->where(function($q) use ($search) {
            $q->where('booking_id', 'like', '%' . $search . '%')
              ->orWhere('hotel_name', 'like', '%' . $search . '%')
              ->orWhere('route', 'like', '%' . $search . '%')
              ->orWhere('employee_name', 'like', '%' . $search . '%')
              ->orWhere('airline_id', 'like', '%' . $search . '%');
        });
    }
}
