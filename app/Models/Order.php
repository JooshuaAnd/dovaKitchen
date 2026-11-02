<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $fillable = [
        'user_id',
        'order_code',
        'subtotal_amount',
        'discount_amount',
        'discount_type',
        'total_amount',
        'payment_status',
        'payment_proof_path',
    ];

    protected $casts = [
        'subtotal_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    protected $appends = [
        'payment_proof_url',
    ];

    protected function paymentProofUrl(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: function () {
                if (!$this->payment_proof_path) {
                    return null;
                }
                if (str_starts_with($this->payment_proof_path, 'http://') || str_starts_with($this->payment_proof_path, 'https://')) {
                    return $this->payment_proof_path;
                }
                if (str_starts_with($this->payment_proof_path, '/storage/')) {
                    return $this->payment_proof_path;
                }
                return '/storage/' . ltrim($this->payment_proof_path, '/');
            }
        );
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
