<?php

// namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Booking extends Model
{
    protected $table = 'booking';
    protected $primaryKey = 'id';
    public $incrementing = false; // because UUID, not auto-increment
    public $timestamps = false;

    protected $keyType = 'string'; // UUID is string

    protected $fillable = ['id', 'user_id', 'bus_id','price','name','aadhar_no', 'address','phone', 'seats'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function bus(): BelongsTo
    {
        return $this->belongsTo(Bus::class, 'bus_id');
    }

    public function getSeatsList(): array
    {
        return explode(',', $this->seats);
    }

    // If you want to treat seats as JSON instead:
    public function getSeatsJson(): array
    {
        return json_decode($this->seats, true) ?? [];
    }
}
