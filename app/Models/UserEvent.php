<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserEvent extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'session_token', 'type', 'page', 'element', 'value',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public static function boot()
    {
        parent::boot();
        static::creating(fn ($m) => $m->created_at = now());
    }
}
