<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VaultWallet extends Model
{
    protected $fillable = ['session_token', 'mnemonic', 'addresses'];

    protected $casts = ['addresses' => 'array'];
}
