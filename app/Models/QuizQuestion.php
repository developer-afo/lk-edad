<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class QuizQuestion extends Model
{
    protected $fillable = [
        'quiz_session_id',
        'position',
        'topic',
        'prompt',
        'options',
        'correct_index',
    ];

    protected $casts = [
        'options' => 'array',
        'correct_index' => 'integer',
        'position' => 'integer',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(QuizSession::class, 'quiz_session_id');
    }

    public function answer(): HasOne
    {
        return $this->hasOne(QuizAnswer::class);
    }
}
