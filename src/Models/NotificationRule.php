<?php

declare(strict_types=1);

namespace NotifyManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NotificationRule extends Model
{
    protected $table = 'notification_rules';

    protected $fillable = [
        'name',
        'channel',
        'conditions',
        'is_active',
        'start_date',
        'end_date',
        'max_sends_per_day',
        'max_sends_per_hour',
        'allowed_days',
        'allowed_hours',
        'priority',
        'metadata',
    ];

    protected $casts = [
        'conditions' => 'array',
        'is_active' => 'boolean',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'max_sends_per_day' => 'integer',
        'max_sends_per_hour' => 'integer',
        'allowed_days' => 'array',
        'allowed_hours' => 'array',
        'priority' => 'integer',
        'metadata' => 'array',
    ];

    public function logs(): HasMany
    {
        return $this->hasMany(NotificationLog::class, 'channel', 'channel');
    }
}
