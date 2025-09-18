<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;  // Ajout du trait Sanctum
use Illuminate\Notifications\Notifiable;

/**
 * Class User
 * 
 * @property int $id_user
 * @property string $first_name
 * @property string $last_name
 * @property string $email
 * @property string $password
 * @property string|null $phone
 * @property string|null $address
 * @property int $id_role
 * @property Carbon $registration_date
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Role $role
 * @property Collection|Reservation[] $reservations
 *
 * @package App\Models
 */
class User extends Authenticatable
{
    use HasApiTokens, Notifiable;
    
    protected $connection = 'pgsql';
    protected $table = 'users';
    protected $primaryKey = 'id_user';

    protected $casts = [
        'id_role' => 'int',
        'registration_date' => 'datetime'
    ];

    protected $hidden = [
        'password'
    ];

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
        'phone',
        'address',
        'id_role',
        'registration_date'
    ];

    /**
     * Hash the password automatically when setting it
     */
    public function setPasswordAttribute($value)
    {
        $this->attributes['password'] = Hash::make($value);
    }

    /**
     * Relation with Role
     */
    public function role()
    {
        return $this->belongsTo(Role::class, 'id_role');
    }

    /**
     * Relation with Reservations
     */
    public function reservations()
    {
        return $this->hasMany(Reservation::class, 'id_user');
    }
}
