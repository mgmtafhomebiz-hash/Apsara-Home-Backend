<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebPageContent extends Model
{
    protected $table = 'tbl_web_page_content';
    protected $primaryKey = 'wpc_id';

    protected $fillable = [
        'wpc_type',
        'wpc_key',
        'wpc_title',
        'wpc_subtitle',
        'wpc_body',
        'wpc_image_url',
        'wpc_link_url',
        'wpc_button_text',
        'wpc_payload',
        'wpc_sort',
        'wpc_status',
        'wpc_start_at',
        'wpc_end_at',
    ];

    protected $casts = [
        'wpc_payload' => 'array',
        'wpc_status' => 'boolean',
        'wpc_sort' => 'integer',
        'wpc_start_at' => 'datetime',
        'wpc_end_at' => 'datetime',
    ];
}

