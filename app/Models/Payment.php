<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Payment
 * 
 * @property int $id_payment
 * @property int $id_reservation
 * @property float $amount
 * @property string $payment_method
 * @property Carbon $payment_date
 * @property string $status
 * @property string|null $transaction_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Reservation $reservation
 *
 * @package App\Models
 */
class Payment extends Model
{
    protected $connection = 'pgsql';
    protected $table = 'payments';
    protected $primaryKey = 'id_payment';

    protected $casts = [
        'id_reservation' => 'int',
        'amount' => 'float',
        'payment_date' => 'datetime'
    ];

    protected $fillable = [
        'id_reservation',
        'amount',
        'payment_method',
        'payment_date',
        'status',
        'transaction_id'
    ];

    public function reservation()
    {
        return $this->belongsTo(Reservation::class, 'id_reservation');
    }
}
