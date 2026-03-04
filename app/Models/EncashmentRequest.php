<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EncashmentRequest extends Model
{
    protected $table = 'tbl_encashment_requests';
    protected $primaryKey = 'er_id';

    protected $fillable = [
        'er_reference_no',
        'er_invoice_no',
        'er_customer_id',
        'er_amount',
        'er_channel',
        'er_account_name',
        'er_account_number',
        'er_notes',
        'er_status',
        'er_admin_notes',
        'er_accounting_notes',
        'er_proof_url',
        'er_proof_public_id',
        'er_proof_uploaded_by',
        'er_proof_uploaded_at',
        'er_approved_by',
        'er_approved_at',
        'er_released_by',
        'er_released_at',
    ];

    protected $casts = [
        'er_amount' => 'float',
        'er_proof_uploaded_at' => 'datetime',
        'er_approved_at' => 'datetime',
        'er_released_at' => 'datetime',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'er_customer_id', 'c_userid');
    }
}
