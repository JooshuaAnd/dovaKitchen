<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VoucherTemplate extends Model
{
    protected $fillable = [
        'title', 'code_prefix', 'rfm_segment', 'discount_type', 'discount_value',
        'min_purchase', 'valid_until', 'notes',
    ];

    protected $casts = [
        'discount_value' => 'decimal:2',
        'min_purchase' => 'decimal:2',
        'valid_until' => 'datetime',
    ];
}
