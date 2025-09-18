<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Bundle
 * 
 * @property int $id_bundle
 * @property string $name
 * @property string|null $description
 * @property float $daily_price
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Collection|Product[] $products
 * @property Collection|Reservation[] $reservations
 *
 * @package App\Models
 */
class Bundle extends Model
{
    protected $connection = 'pgsql';
    protected $table = 'bundles';
    protected $primaryKey = 'id_bundle';

    protected $casts = [
        'daily_price' => 'float',
        'is_active' => 'bool'
    ];

    protected $fillable = [
        'name',
        'description',
        'daily_price',
        'is_active'
    ];

    public function products()
    {
        return $this->belongsToMany(Product::class, 'bundle_products', 'id_bundle', 'id_product')
                    ->withPivot('quantity');
    }

    public function reservations()
    {
        return $this->belongsToMany(Reservation::class, 'reservation_bundles', 'id_bundle', 'id_reservation')
                    ->withPivot('id_reservation_bundle', 'quantity')
                    ->withTimestamps();
    }
}
