<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupplierCategoryAccess extends Model
{
    protected $table = 'tbl_supplier_category_access';

    public $timestamps = false;

    protected $fillable = [
        'supplier_id',
        'category_id',
        'created_at',
    ];

    protected $casts = [
        'supplier_id' => 'integer',
        'category_id' => 'integer',
    ];
}
