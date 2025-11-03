<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubSpace extends Model
{
    use HasFactory;

    protected $fillable = [
        'parking_space_id',
        'parking_spot_id',
        'label',
        'status',
        'latitude',
        'longitude',
    'current_user_id', 
    ];

    public function parkingSpace()
    {
        return $this->belongsTo(ParkingSpace::class);
    }

    public function parkingSpot()
    {
        return $this->belongsTo(ParkingSpot::class);
    }

    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }
}