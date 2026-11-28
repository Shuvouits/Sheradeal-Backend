<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $fillable = [
        'order_number',
        'user_id',
        'source',
        'customer_name',
        'customer_phone',
        'customer_email',
        'delivery_address',
        'shipping_location',
        'customer_note',
        'status',
        'payment_method',
        'payment_status',
        'subtotal',
        'discount_total',
        'delivery_charge',
        'grand_total',
        'confirmed_at',
        'shipped_at',
        'delivered_at',
        'cancelled_at',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'discount_total' => 'decimal:2',
            'delivery_charge' => 'decimal:2',
            'grand_total' => 'decimal:2',
            'confirmed_at' => 'datetime',
            'shipped_at' => 'datetime',
            'delivered_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function timeline(): HasMany
    {
        return $this->hasMany(OrderTimeline::class)
            ->orderByDesc('id');
    }
}
