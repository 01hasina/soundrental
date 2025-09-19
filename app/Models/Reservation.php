<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Reservation
 * 
 * @property int $id_reservation
 * @property int $id_user
 * @property Carbon $event_date
 * @property time without time zone $event_time
 * @property int $duration_hours
 * @property string|null $location
 * @property string $status
 * @property float|null $estimated_price
 * @property float|null $final_price
 * @property string $order_state
 * @property Carbon $reservation_date
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property User $user
 * @property Collection|Product[] $products
 * @property Collection|Bundle[] $bundles
 * @property Collection|Payment[] $payments
 * @property Collection|Quote[] $quotes
 * @property Collection|Invoice[] $invoices
 *
 * @package App\Models
 */
class Reservation extends Model
{
    protected $connection = 'pgsql';
    protected $table = 'reservations';
    protected $primaryKey = 'id_reservation';

    protected $casts = [
        'id_user' => 'int',
        'event_date' => 'datetime',
        'event_time' => 'datetime:H:i:s',
        'duration_hours' => 'int',
        'estimated_price' => 'float',
        'final_price' => 'float',
        'reservation_date' => 'datetime'
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
        'reservation_date'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'reservation_products', 'id_reservation', 'id_product')
                    ->withPivot('id_reservation_product', 'quantity')
                    ->withTimestamps();
    }

    public function bundles()
    {
        return $this->belongsToMany(Bundle::class, 'reservation_bundles', 'id_reservation', 'id_bundle')
                    ->withPivot('id_reservation_bundle', 'quantity')
                    ->withTimestamps();
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
