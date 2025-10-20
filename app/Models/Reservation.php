<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    protected $connection = 'pgsql';
    protected $table = 'reservations';
    protected $primaryKey = 'id_reservation';

    const CREATED_AT = 'reservation_date';
    const UPDATED_AT = 'updated_at';

    protected $casts = [
        'id_user' => 'int',
        'event_date' => 'date',
        'event_time' => 'string',
        'duration_hours' => 'int',
        'estimated_price' => 'float',
        'final_price' => 'float',
        'reservation_date' => 'datetime',
    ];

    protected $fillable = [
        'id_user',
        'event_date',
        'event_time',
        'duration_hours',
        'location',
        'status',
        'estimated_price',
        'final_price',
        'order_state',
        'reservation_date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'reservation_inventory', 'id_reservation', 'id_inventory')
                    ->withPivot('id_inventory');
    }

    public function bundles()
    {
        return $this->belongsToMany(Bundle::class, 'reservation_bundles', 'id_reservation', 'id_bundle')
                    ->withPivot('id_reservation_bundle', 'quantity'); // PAS de withTimestamps()
    }

    public function payments()
    {
        return $this->hasMany(Payment::class, 'id_reservation');
    }

    public function quotes()
    {
        return $this->hasMany(Quote::class, 'id_reservation');
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class, 'id_reservation');
    }
}

