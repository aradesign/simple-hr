<?php

namespace App\Models;

use App\Domain\Enums\NotificationStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationLog extends Model
{
    protected $fillable = [
        'user_id',
        'person_id',
        'channel',
        'recipient',
        'subject',
        'body',
        'status',
        'error_message',
    ];

    protected $casts = [
            'status' => NotificationStatus::class,
        ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function scopeSent(Builder $query): Builder
    {
        return $query->where('status', NotificationStatus::Sent);
    }

    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('status', NotificationStatus::Failed);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', NotificationStatus::Pending);
    }

    public function scopeByChannel(Builder $query, string $channel): Builder
    {
        return $query->where('channel', $channel);
    }
}
