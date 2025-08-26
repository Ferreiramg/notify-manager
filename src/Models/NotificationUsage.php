<?php

declare(strict_types=1);

namespace NotifyManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class NotificationUsage extends Model
{
    protected $table = 'notification_usages';

    protected $fillable = [
        'notification_id',
        'channel',
        'cost',
        'used_at',
        'metadata',
    ];

    protected $casts = [
        'cost' => 'decimal:4',
        'used_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function log(): HasOne
    {
        return $this->hasOne(NotificationLog::class, 'notification_id', 'notification_id');
    }
}
