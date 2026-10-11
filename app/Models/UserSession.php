<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserSession extends Model
{
    protected $fillable = [
        'session_token', 'label', 'ip_address', 'user_agent',
        'current_page', 'country', 'enc_key', 'document_id',
        'document_issued_at',
        'last_seen_at', 'last_active_at', 'pinned',
    ];

    protected $casts = [
        'last_seen_at'   => 'datetime',
        'last_active_at' => 'datetime',
        'document_issued_at' => 'integer',
        'metadata'       => 'array',
        'activity_summary' => 'array',
    ];

    public function events()
    {
        return $this->hasMany(UserEvent::class, 'session_token', 'session_token');
    }

    public function pendingCommands()
    {
        return $this->hasMany(AdminCommand::class, 'session_token', 'session_token')
            ->where('executed', false)
            ->orderBy('created_at');
    }

    public function isActive(): bool
    {
        return $this->last_seen_at && $this->last_seen_at->diffInSeconds(now()) < 300;
    }
}
