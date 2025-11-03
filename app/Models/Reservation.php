<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'parking_space_id',
        'parking_spot_id',
        'sub_space_id',
        'type',
        'status',
        'start_time',
        'end_time',
        'vehicle_number',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function parkingSpace()
    {
        return $this->belongsTo(ParkingSpace::class);
    }

    public function parkingSpot()
    {
        return $this->belongsTo(ParkingSpot::class);
    }

    public function subSpace()
    {
        return $this->belongsTo(SubSpace::class);
    }
}