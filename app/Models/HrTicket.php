<?php

namespace App\Models;

use App\Domain\Enums\HrTicketStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HrTicket extends Model
{
    protected $fillable = [
        'person_id',
        'subject',
        'message',
        'status',
        'hr_reply',
        'assigned_to',
        'replied_at',
    ];

    protected $casts = [
            'status' => HrTicketStatus::class,
            'replied_at' => 'datetime',
        ];

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
