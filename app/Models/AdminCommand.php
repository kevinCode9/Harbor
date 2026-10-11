<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminCommand extends Model
{
    protected $fillable = [
        'session_token', 'command', 'payload', 'executed', 'executed_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'executed' => 'boolean',
        'executed_at' => 'datetime',
    ];

    public function markExecuted(): void
    {
        $this->update(['executed' => true, 'executed_at' => now()]);
    }
}
