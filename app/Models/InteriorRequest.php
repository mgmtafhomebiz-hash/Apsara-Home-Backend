<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InteriorRequest extends Model
{
    protected $table = 'tbl_interior_requests';
    protected $primaryKey = 'ir_id';

    protected $fillable = [
        'ir_customer_id',
        'ir_reference',
        'ir_service_type',
        'ir_project_type',
        'ir_property_type',
        'ir_project_scope',
        'ir_budget',
        'ir_style_preference',
        'ir_preferred_date',
        'ir_preferred_time',
        'ir_flexibility',
        'ir_target_timeline',
        'ir_first_name',
        'ir_last_name',
        'ir_email',
        'ir_phone',
        'ir_notes',
        'ir_referral',
        'ir_inspiration_files',
        'ir_status',
        'ir_priority',
        'ir_assigned_admin_id',
    ];

    protected $casts = [
        'ir_customer_id' => 'integer',
        'ir_assigned_admin_id' => 'integer',
        'ir_inspiration_files' => 'array',
        'ir_preferred_date' => 'date',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'ir_customer_id', 'c_userid');
    }

    public function assignedAdmin()
    {
        return $this->belongsTo(Admin::class, 'ir_assigned_admin_id', 'id');
    }

    public function updates()
    {
        return $this->hasMany(InteriorRequestUpdate::class, 'iru_request_id', 'ir_id')
            ->orderByDesc('created_at')
            ->orderByDesc('iru_id');
    }
}
