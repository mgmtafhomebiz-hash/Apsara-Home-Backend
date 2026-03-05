<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Wishlist extends Model
{
    protected $table = 'tbl_customer_wishlist';
    protected $primaryKey = 'cw_id';
    public $timestamps = false;

    protected $guarded = [];

    public function product()
    {
        return $this->belongsTo(Product::class, 'cw_product_id', 'pd_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'cw_customer_id', 'c_userid');
    }
}
