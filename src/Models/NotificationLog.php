<?php

declare(strict_types=1);

namespace NotifyManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationLog extends Model
{
    protected $table = 'notification_logs';

    protected $fillable = [
        'notification_id',
        'channel',
        'recipient',
        'message',
        'status',
        'response',
        'metadata',
        'sent_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'sent_at' => 'datetime',
    ];

    public function rule(): BelongsTo
    {
        return $this->belongsTo(NotificationRule::class, 'channel', 'channel');
    }

    public function usage(): BelongsTo
    {
        return $this->belongsTo(NotificationUsage::class, 'notification_id', 'notification_id');
    }
}
