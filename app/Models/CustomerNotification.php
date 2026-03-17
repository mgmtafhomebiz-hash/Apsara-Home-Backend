<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerNotification extends Model
{
    protected $table = 'tbl_customer_notifications';
    protected $primaryKey = 'cn_id';
    public $timestamps = false;

    protected $fillable = [
        'cn_customer_id',
        'cn_type',
        'cn_severity',
        'cn_title',
        'cn_message',
        'cn_href',
        'cn_payload',
        'cn_source_type',
        'cn_source_id',
        'cn_created_at',
    ];

    protected $casts = [
        'cn_customer_id' => 'integer',
        'cn_payload' => 'array',
        'cn_source_id' => 'integer',
        'cn_created_at' => 'datetime',
    ];
}
