<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubAdminAccount extends Model
{
    protected $fillable = ['label', 'access_key', 'is_active', 'last_login_at', 'last_active_at'];

    protected $casts = [
        'is_active'      => 'boolean',
        'last_login_at'  => 'datetime',
        'last_active_at' => 'datetime',
    ];
}
