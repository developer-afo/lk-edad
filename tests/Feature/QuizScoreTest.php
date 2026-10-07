<?php

namespace Tests\Feature;

use App\Models\QuizSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuizScoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_topic_percentages_come_from_correct_answers(): void
    {
        $user = User::factory()->create();
        $session = QuizSession::query()->create([
            'user_id' => $user->id,
            'pdf_original_name' => 'guide.pdf',
            'status' => QuizSession::DONE,
            'question_count' => 4,
        ]);

        $community = [];
        foreach (['Community', 'Community', 'Policy', 'Policy'] as $index => $topic) {
            $question = $session->questions()->create([
                'position' => $index + 1,
                'topic' => $topic,
                'prompt' => 'Q '.$index,
                'options' => ['A', 'B', 'C', 'D'],
                'correct_index' => 0,
            ]);
            $session->answers()->create([
                'quiz_question_id' => $question->id,
                'chosen_index' => $topic === 'Community' ? 0 : 1,
            ]);
            $community[] = $question;
        }

        $score = $session->fresh()->scorecard();

        $this->assertSame(50, $score['overall']);
        $this->assertSame('Community', $score['topics'][0]['topic']);
        $this->assertSame(100, $score['topics'][0]['percent']);
        $this->assertSame('Policy', $score['topics'][1]['topic']);
        $this->assertSame(0, $score['topics'][1]['percent']);
        $this->assertCount(4, $community);
    }
}
