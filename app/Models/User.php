<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'email_verified_at',
        'embed_token',
        'embed_token_expires_at',
    ];

    protected $hidden = [
        'embed_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'embed_token_expires_at' => 'datetime',
    ];

    public function quizSessions(): HasMany
    {
        return $this->hasMany(QuizSession::class);
    }
}
