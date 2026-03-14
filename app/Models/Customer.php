<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'tbl_customer';

    protected $primaryKey = 'c_userid';

    public $incrementing = true;

    protected $keyType = 'int';

    public $timestamps = false;

    protected $guarded = [];

    protected $hidden = [
        'c_password',
        'c_password_pin',
        'remember_token',
    ];

    public function addresses(): HasMany
    {
        return $this->hasMany(CustomerAddress::class, 'a_cid', 'c_userid');
    }
}
