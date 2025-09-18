<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Product
 * 
 * @property int $id_product
 * @property string $name
 * @property string|null $description
 * @property float $daily_price
 * @property float|null $replacement_cost
 * @property bool $is_active
 * @property int|null $id_category
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Category|null $category
 * @property Collection|Bundle[] $bundles
 * @property Collection|Inventory[] $inventories
 * @property Collection|Reservation[] $reservations
 *
 * @package App\Models
 */
class Product extends Model
{
    protected $connection = 'pgsql';
    protected $table = 'products';
    protected $primaryKey = 'id_product';

    protected $casts = [
        'daily_price' => 'float',
        'replacement_cost' => 'float',
        'is_active' => 'bool',
        'id_category' => 'int'
    ];

    protected $fillable = [
        'name',
        'description',
        'daily_price',
        'replacement_cost',
        'is_active',
        'id_category'
    ];

    public function category()
    {
        return $this->belongsTo(Category::class, 'id_category');
    }

    public function bundles()
    {
        return $this->belongsToMany(Bundle::class, 'bundle_products', 'id_product', 'id_bundle')
                    ->withPivot('quantity');
    }

    public function inventories()
    {
        return $this->hasMany(Inventory::class, 'id_product');
    }

    public function reservations()
    {
        return $this->belongsToMany(Reservation::class, 'reservation_products', 'id_product', 'id_reservation')
                    ->withPivot('id_reservation_product', 'quantity')
                    ->withTimestamps();
    }
}
