<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerWalletLedger extends Model
{
    protected $table = 'tbl_customer_wallet_ledger';
    protected $primaryKey = 'wl_id';

    protected $fillable = [
        'wl_customer_id',
        'wl_wallet_type',
        'wl_entry_type',
        'wl_amount',
        'wl_source_type',
        'wl_source_id',
        'wl_reference_no',
        'wl_notes',
        'wl_created_by',
    ];

    protected $casts = [
        'wl_amount' => 'float',
    ];
}

