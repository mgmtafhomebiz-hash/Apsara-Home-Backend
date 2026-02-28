<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductPhoto extends Model
{
    protected $table = 'tbl_product_photo';
    protected $primaryKey = 'pp_id';
    public $timestamps = false;

    protected $fillable = [
        'pp_pdid',
        'pp_filename',
        'pp_varone',
        'pp_date',
    ];

    protected $casts = [
        'pp_date' => 'datetime',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'pp_pdid', 'pd_id');
    }
}

