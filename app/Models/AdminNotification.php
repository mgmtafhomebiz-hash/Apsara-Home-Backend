<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminNotification extends Model
{
    protected $table = 'tbl_admin_notifications';
    protected $primaryKey = 'an_id';
    public $timestamps = false;

    protected $fillable = [
        'an_type',
        'an_severity',
        'an_title',
        'an_message',
        'an_href',
        'an_payload',
        'an_source_type',
        'an_source_id',
        'an_created_at',
    ];

    protected $casts = [
        'an_payload' => 'array',
        'an_source_id' => 'integer',
        'an_created_at' => 'datetime',
    ];
}

