<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Voucher extends Model
{
    protected $fillable = [
        'user_id',
        'code',
        'discount_type',
        'discount_value',
        'min_purchase',
        'valid_until',
        'rfm_segment',
        'title',
        'notes',
        'is_used',
        'used_at',
        'used_in_order_id',
    ];

    protected $casts = [
        'discount_value' => 'decimal:2',
        'min_purchase' => 'decimal:2',
        'valid_until' => 'datetime',
        'is_used' => 'boolean',
        'used_at' => 'datetime',
    ];

    protected $appends = [
        'is_expired',
        'status_label',
        'formatted_discount',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'used_in_order_id');
    }

    public function getIsExpiredAttribute(): bool
    {
        return Carbon::now()->greaterThan($this->valid_until);
    }

    public function getStatusLabelAttribute(): string
    {
        if ($this->is_used) {
            return 'Sudah Digunakan';
        }
        if ($this->is_expired) {
            return 'Kedaluwarsa';
        }
        return 'Tersedia';
    }

    public function getFormattedDiscountAttribute(): string
    {
        if ($this->discount_type === 'percentage') {
            return round($this->discount_value) . '%';
        }
        return 'Rp ' . number_format($this->discount_value, 0, ',', '.');
    }
}
