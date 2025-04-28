<?php

// namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Bus extends Model
{
    protected $table = 'buses';
    public $timestamps = false;

    protected $fillable = ['name', 'from_place', 'to_place', 'date','time'];

    public function bookings(): HasMany{
        return $this->hasMany(Booking::class, 'bus_id');
    }
}
