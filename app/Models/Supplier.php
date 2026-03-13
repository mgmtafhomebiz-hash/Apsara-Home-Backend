<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    protected $table = 'tbl_supplier';
    protected $primaryKey = 's_id';
    public $timestamps = false;

    protected $fillable = [
        's_name',
        's_company',
        's_email',
        's_contact',
        's_address',
        's_status',
    ];
}
