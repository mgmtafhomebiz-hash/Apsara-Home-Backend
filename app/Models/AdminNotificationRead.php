<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminNotificationRead extends Model
{
    protected $table = 'tbl_admin_notification_reads';
    protected $primaryKey = 'anr_id';
    public $timestamps = false;

    protected $fillable = [
        'anr_notification_id',
        'anr_admin_id',
        'anr_read_at',
    ];

    protected $casts = [
        'anr_notification_id' => 'integer',
        'anr_admin_id' => 'integer',
        'anr_read_at' => 'datetime',
    ];
}

