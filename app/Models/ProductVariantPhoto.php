<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductVariantPhoto extends Model
{
    protected $table = 'tbl_product_variant_photo';
    protected $primaryKey = 'pvp_id';
    public $timestamps = false;

    protected $fillable = [
        'pvp_pvid',
        'pvp_filename',
        'pvp_sort',
        'pvp_date',
    ];

    protected $casts = [
        'pvp_sort' => 'integer',
        'pvp_date' => 'datetime',
    ];

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'pvp_pvid', 'pv_id');
    }
}

