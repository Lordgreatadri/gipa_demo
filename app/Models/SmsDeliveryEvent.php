<?php

namespace App\Models;

use App\Models\SmsMessage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmsDeliveryEvent extends Model
{
    protected $fillable = [
        'sms_message_id',
        'provider',
        'provider_message_id',
        'status',
        'raw_status',
        'detail',
        'webhook_call_id',
        'payload',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'occurred_at' => 'datetime',
        ];
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(SmsMessage::class, 'sms_message_id');
    }
}
