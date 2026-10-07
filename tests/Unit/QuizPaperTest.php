<?php

namespace Tests\Unit;

use App\Support\QuizPaper;
use InvalidArgumentException;
use Tests\TestCase;

class QuizPaperTest extends TestCase
{
    public function test_it_reads_a_fenced_json_quiz(): void
    {
        $questions = [];
        foreach (['Community', 'Policy', 'Health', 'Finance'] as $topic) {
            for ($i = 0; $i < 3; $i++) {
                $questions[] = $this->question($topic, count($questions));
            }
        }

        $reply = "```json\n".json_encode(['questions' => $questions])."\n```";
        $paper = QuizPaper::fromModelReply($reply);

        $this->assertCount(12, $paper);
        $this->assertSame('Community', $paper[0]['topic']);
        $this->assertSame(0, $paper[0]['correct_index']);
    }

    public function test_it_keeps_at_most_eight_topics_and_twenty_five_questions(): void
    {
        $questions = [];
        for ($topic = 1; $topic <= 10; $topic++) {
            for ($i = 0; $i < 3; $i++) {
                $questions[] = $this->question('Topic '.$topic, count($questions));
            }
        }

        $paper = QuizPaper::fromModelReply(json_encode(['questions' => $questions]));

        $this->assertCount(25, $paper);
        $this->assertCount(8, array_unique(array_column($paper, 'topic')));
    }

    public function test_it_keeps_finished_questions_when_the_json_is_cut_off(): void
    {
        $questions = [];
        for ($i = 0; $i < 12; $i++) {
            $questions[] = $this->question($i % 2 === 0 ? 'Community' : 'Policy', $i);
        }

        $body = substr(json_encode($questions), 1, -1);
        $reply = '{"questions":['.$body.', {"topic": "Policy", "prompt": "Cut off';

        $paper = QuizPaper::fromModelReply($reply);

        $this->assertGreaterThanOrEqual(10, count($paper));
        $this->assertLessThanOrEqual(12, count($paper));
    }

    public function test_it_rejects_a_short_quiz(): void
    {
        $this->expectException(InvalidArgumentException::class);

        QuizPaper::fromModelReply(json_encode([
            'questions' => [$this->question('Community', 1)],
        ]));
    }

    private function question(string $topic, int $n): array
    {
        return [
            'topic' => $topic,
            'prompt' => 'Question '.$n.' about '.$topic.'?',
            'options' => ['One', 'Two', 'Three', 'Four'],
            'correct_index' => $n % 4,
        ];
    }
}
