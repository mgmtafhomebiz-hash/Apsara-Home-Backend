<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InteriorRequestUpdate extends Model
{
    protected $table = 'tbl_interior_request_updates';
    protected $primaryKey = 'iru_id';

    protected $fillable = [
        'iru_request_id',
        'iru_actor_admin_id',
        'iru_type',
        'iru_title',
        'iru_message',
        'iru_payload',
        'iru_visible_to_customer',
    ];

    protected $casts = [
        'iru_request_id' => 'integer',
        'iru_actor_admin_id' => 'integer',
        'iru_payload' => 'array',
        'iru_visible_to_customer' => 'boolean',
    ];

    public function request()
    {
        return $this->belongsTo(InteriorRequest::class, 'iru_request_id', 'ir_id');
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class, 'iru_actor_admin_id', 'id');
    }
}
