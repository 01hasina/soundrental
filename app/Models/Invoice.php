<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Invoice
 * 
 * @property int $id_invoice
 * @property int $id_reservation
 * @property float|null $total_amount
 * @property Carbon $billing_date
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Reservation $reservation
 *
 * @package App\Models
 */
class Invoice extends Model
{
    protected $connection = 'pgsql';
    protected $table = 'invoices';
    protected $primaryKey = 'id_invoice';

    protected $casts = [
        'id_reservation' => 'int',
        'total_amount' => 'float',
        'billing_date' => 'datetime'
    ];

    protected $fillable = [
        'id_reservation',
        'total_amount',
        'billing_date'
    ];

    public function reservation()
    {
        return $this->belongsTo(Reservation::class, 'id_reservation');
    }
}
