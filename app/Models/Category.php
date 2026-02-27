<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $table      = 'tbl_category';
    protected $primaryKey = 'cat_id';
    public    $incrementing = true;
    public    $timestamps   = false;

    protected $fillable = [
        'cat_name',
        'cat_description',
        'cat_url',
        'cat_image',
        'cat_order',
    ];

    protected $casts = [
        'cat_order' => 'integer',
    ];
}
