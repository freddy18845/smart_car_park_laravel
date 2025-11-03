<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'first_name',
        'last_name',
        'phone',
        'password',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'password' => 'hashed',
    ];
     protected $appends = ['fullname'];

    // Virtual fullname attribute
    public function getFullnameAttribute()
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function parkingSpaces()
    {
        return $this->hasMany(ParkingSpace::class, 'operator_id');
    }

    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }
    // app/Models/Reservation.php
public function user()
{
    return $this->belongsTo(User::class, 'user_id');
}

}