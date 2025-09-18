<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Quote
 * 
 * @property int $id_quote
 * @property int $id_reservation
 * @property float|null $total_ht
 * @property float|null $vat
 * @property float|null $total_ttc
 * @property Carbon $issue_date
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Reservation $reservation
 *
 * @package App\Models
 */
class Quote extends Model
{
    protected $connection = 'pgsql';
    protected $table = 'quotes';
    protected $primaryKey = 'id_quote';

    protected $casts = [
        'id_reservation' => 'int',
        'total_ht' => 'float',
        'vat' => 'float',
        'total_ttc' => 'float',
        'issue_date' => 'datetime'
    ];

    protected $fillable = [
        'id_reservation',
        'total_ht',
        'vat',
        'total_ttc',
        'issue_date'
    ];

    public function reservation()
    {
        return $this->belongsTo(Reservation::class, 'id_reservation');
    }
}
