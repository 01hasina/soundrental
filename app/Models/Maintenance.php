<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Maintenance
 * 
 * @property int $id_maintenance
 * @property int $id_inventory
 * @property Carbon $start_date
 * @property Carbon $end_date
 * @property string $description
 * @property float|null $cost
 * @property string $status
 * 
 * @property Inventory $inventory
 *
 * @package App\Models
 */
class Maintenance extends Model
{
    protected $connection = 'pgsql';
    protected $table = 'maintenance';
    protected $primaryKey = 'id_maintenance';
    public $timestamps = false;

    protected $casts = [
        'id_inventory' => 'int',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'cost' => 'float'
    ];

    protected $fillable = [
        'id_inventory',
        'start_date',
        'end_date',
        'description',
        'cost',
        'status'
    ];

    public function inventory()
    {
        return $this->belongsTo(Inventory::class, 'id_inventory');
    }
}
