<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ParkingSpace extends Model
{
    use HasFactory;

    protected $fillable = [
        'operator_id',
        'name',
        'location',
        'phone',
        'other_contact',
        'latitude',
        'longitude',
    ];

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
    ];

    public function operator()
    {
        return $this->belongsTo(User::class, 'operator_id');
    }

    public function parkingSpots()
    {
        return $this->hasMany(ParkingSpot::class);
    }

    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }
}