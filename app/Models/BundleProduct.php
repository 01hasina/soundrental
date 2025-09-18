<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class BundleProduct
 * 
 * @property int $id_bundle
 * @property int $id_product
 * @property int $quantity
 * 
 * @property Bundle $bundle
 * @property Product $product
 *
 * @package App\Models
 */
class BundleProduct extends Model
{
    protected $connection = 'pgsql';
    protected $table = 'bundle_products';
    public $incrementing = false;
    public $timestamps = false;

    protected $casts = [
        'id_bundle' => 'int',
        'id_product' => 'int',
        'quantity' => 'int'
    ];

    protected $fillable = [
        'quantity'
    ];

    public function bundle()
    {
        return $this->belongsTo(Bundle::class, 'id_bundle');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'id_product');
    }
}
