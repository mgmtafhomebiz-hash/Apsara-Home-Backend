<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerVerificationRequest extends Model
{
    protected $table = 'tbl_customer_verification_requests';
    protected $primaryKey = 'cvr_id';

    protected $fillable = [
        'cvr_customer_id',
        'cvr_reference_no',
        'cvr_status',
        'cvr_full_name',
        'cvr_birth_date',
        'cvr_id_type',
        'cvr_id_number',
        'cvr_contact_number',
        'cvr_address_line',
        'cvr_city',
        'cvr_province',
        'cvr_postal_code',
        'cvr_country',
        'cvr_notes',
        'cvr_id_front_url',
        'cvr_id_back_url',
        'cvr_selfie_url',
        'cvr_profile_photo_url',
        'cvr_reviewed_by',
        'cvr_review_notes',
        'cvr_reviewed_at',
    ];

    protected $casts = [
        'cvr_birth_date' => 'date',
        'cvr_reviewed_at' => 'datetime',
    ];
}
