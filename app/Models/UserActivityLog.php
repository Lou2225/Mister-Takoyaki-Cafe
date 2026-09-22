<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserActivityLog extends Model
{
    protected $fillable = [
        'user_id',
        'performed_by',
        'event_type',
        'description',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ── Relationships ─────────────────────────────────────────────

    /** The user this event belongs to. */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /** The staff member who performed the action (nullable). */
    public function performer()
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    // ── Helpers ───────────────────────────────────────────────────

    /** Human-readable label for the event type. */
    public function getEventLabelAttribute(): string
    {
        return match ($this->event_type) {
            'hired'       => 'Joined the Team',
            'activated'   => 'Account Activated',
            'deactivated' => 'Account Deactivated',
            'archived'    => 'Account Archived',
            'restored'    => 'Account Restored',
            default       => ucfirst($this->event_type),
        };
    }

    /** Tailwind colour token for this event type. */
    public function getColorAttribute(): string
    {
        return match ($this->event_type) {
            'hired'       => 'indigo',
            'activated'   => 'emerald',
            'deactivated' => 'amber',
            'archived'    => 'rose',
            'restored'    => 'sky',
            default       => 'slate',
        };
    }

    /** Badge text shown in the timeline pill. */
    public function getBadgeAttribute(): string
    {
        return match ($this->event_type) {
            'hired'       => 'Milestone',
            'activated'   => 'Activated',
            'deactivated' => 'Deactivated',
            'archived'    => 'Archived',
            'restored'    => 'Restored',
            default       => ucfirst($this->event_type),
        };
    }
}

