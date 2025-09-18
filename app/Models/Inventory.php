<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Inventory
 * 
 * @property int $id_inventory
 * @property int $id_product
 * @property string|null $serial_number
 * @property string $condition
 * @property Carbon|null $purchase_date
 * @property Carbon|null $last_maintenance_date
 * @property bool $is_available
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Product $product
 * @property Collection|Maintenance[] $maintenances
 *
 * @package App\Models
 */
class Inventory extends Model
{
    protected $connection = 'pgsql';
    protected $table = 'inventory';
    protected $primaryKey = 'id_inventory';

    protected $casts = [
        'id_product' => 'int',
        'purchase_date' => 'datetime',
        'last_maintenance_date' => 'datetime',
        'is_available' => 'bool'
    ];

    protected $fillable = [
        'id_product',
        'serial_number',
        'condition',
        'purchase_date',
        'last_maintenance_date',
        'is_available',
        'notes'
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'id_product');
    }

    public function maintenances()
    {
        return $this->hasMany(Maintenance::class, 'id_inventory');
    }
}
