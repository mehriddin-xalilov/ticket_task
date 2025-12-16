<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserLoginProviders extends Model
{
    protected $table = 'user_login_providers';

    protected $fillable = [
        'user_id',
        'provider',
        'provider_id',
        'email',
        'full_name',
        'photo',
        'phone',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
