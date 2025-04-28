<?php

// namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Model{
    protected $table = 'users';
    public $timestamps = false;

    protected $fillable = ['username', 'email', 'password'];

    public function bookings(): HasMany{
        return $this->hasMany(Booking::class, 'user_id');
    }
}
