<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuizSession extends Model
{
    public const BUILDING = 'building';

    public const READY = 'ready';

    public const IN_PROGRESS = 'in_progress';

    public const DONE = 'done';

    public const FAILED = 'failed';

    protected $fillable = [
        'user_id',
        'pdf_original_name',
        'pdf_path',
        'status',
        'error',
        'question_count',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(QuizQuestion::class)->orderBy('position');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(QuizAnswer::class);
    }

    public function isPlayable(): bool
    {
        return in_array($this->status, [self::READY, self::IN_PROGRESS], true);
    }

    /**
     * @return array{overall: int, correct: int, total: int, topics: list<array{topic: string, percent: int, correct: int, total: int}>}
     */
    public function scorecard(): array
    {
        $this->loadMissing(['questions', 'answers']);
        $answers = $this->answers->keyBy('quiz_question_id');
        $topics = [];
        $correct = 0;

        foreach ($this->questions as $question) {
            $topics[$question->topic] ??= [
                'topic' => $question->topic,
                'correct' => 0,
                'total' => 0,
            ];
            $topics[$question->topic]['total']++;
            $chosen = $answers->get($question->id);
            if ($chosen && (int) $chosen->chosen_index === (int) $question->correct_index) {
                $topics[$question->topic]['correct']++;
                $correct++;
            }
        }

        $bars = [];
        foreach ($topics as $row) {
            $bars[] = [
                'topic' => $row['topic'],
                'percent' => $row['total'] ? (int) round($row['correct'] / $row['total'] * 100) : 0,
                'correct' => $row['correct'],
                'total' => $row['total'],
            ];
        }

        usort($bars, fn (array $a, array $b) => $b['percent'] <=> $a['percent'] ?: strcmp($a['topic'], $b['topic']));

        $total = $this->questions->count();

        return [
            'overall' => $total ? (int) round($correct / $total * 100) : 0,
            'correct' => $correct,
            'total' => $total,
            'topics' => $bars,
        ];
    }
}
