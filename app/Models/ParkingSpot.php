<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ParkingSpot extends Model
{
    use HasFactory;

    protected $fillable = [
        'parking_space_id',
        'name',
        'status',
        'latitude',
        'longitude',
        'directions',
        'distance',
    ];

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'distance' => 'double',
    ];

    public function parkingSpace()
    {
        return $this->belongsTo(ParkingSpace::class);
    }

    public function subSpaces()
    {
        return $this->hasMany(SubSpace::class);
    }

    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }
}