<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'stripe_payment_intent_id',
        'customer_email',
        'amount',
        'currency',
        'status',
        'description',
        'stripe_event_id',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
        ];
    }
}
