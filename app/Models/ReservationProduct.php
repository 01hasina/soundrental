<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class ReservationProduct
 * 
 * @property int $id_reservation_product
 * @property int $id_reservation
 * @property int $id_product
 * @property int $quantity
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Reservation $reservation
 * @property Product $product
 *
 * @package App\Models
 */
class ReservationProduct extends Model
{
    protected $connection = 'pgsql';
    protected $table = 'reservation_products';
    protected $primaryKey = 'id_reservation_product';

    protected $casts = [
        'id_reservation' => 'int',
        'id_product' => 'int',
        'quantity' => 'int'
    ];

    protected $fillable = [
        'id_reservation',
        'id_product',
        'quantity'
    ];

    public function reservation()
    {
        return $this->belongsTo(Reservation::class, 'id_reservation');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'id_product');
    }
}
