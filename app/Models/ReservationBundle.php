<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class ReservationBundle
 * 
 * @property int $id_reservation_bundle
 * @property int $id_reservation
 * @property int $id_bundle
 * @property int $quantity
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Reservation $reservation
 * @property Bundle $bundle
 *
 * @package App\Models
 */
class ReservationBundle extends Model
{
    protected $connection = 'pgsql';
    protected $table = 'reservation_bundles';
    protected $primaryKey = 'id_reservation_bundle';

    protected $casts = [
        'id_reservation' => 'int',
        'id_bundle' => 'int',
        'quantity' => 'int'
    ];

    protected $fillable = [
        'id_reservation',
        'id_bundle',
        'quantity'
    ];

    public function reservation()
    {
        return $this->belongsTo(Reservation::class, 'id_reservation');
    }

    public function bundle()
    {
        return $this->belongsTo(Bundle::class, 'id_bundle');
    }
}
