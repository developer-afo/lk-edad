<?php

namespace App\Services;

use App\Models\QuizSession;
use App\Support\QuizPaper;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class QuizBuilder
{
    public function __construct(
        private readonly TribePeerService $tribepeer,
    ) {}

    public function build(QuizSession $session, string $documentText): void
    {
        $document = mb_substr(trim($documentText), 0, 14000);
        if (mb_strlen($document) < 80) {
            throw new InvalidArgumentException('That PDF does not have enough text to build a quiz. Try a document with selectable text, not a scan.');
        }

        $collected = [];
        $seen = [];

        for ($round = 0; $round < 3 && count($collected) < 12; $round++) {
            $reply = $this->tribepeer->complete(
                'Reply with one JSON object only. No markdown.',
                $this->prompt($document, $collected, $round)
            );

            foreach (QuizPaper::parse($reply) as $question) {
                $key = mb_strtolower($question['prompt']);
                if (isset($seen[$key])) {
                    continue;
                }
                $seen[$key] = true;
                $collected[] = $question;
            }
        }

        $questions = QuizPaper::finalize($collected);

        DB::transaction(function () use ($session, $questions) {
            foreach ($questions as $index => $question) {
                $session->questions()->create([
                    'position' => $index + 1,
                    'topic' => $question['topic'],
                    'prompt' => $question['prompt'],
                    'options' => $question['options'],
                    'correct_index' => $question['correct_index'],
                ]);
            }

            $session->update([
                'status' => QuizSession::READY,
                'error' => null,
                'question_count' => count($questions),
            ]);
        });
    }

    /**
     * @param  list<array{topic: string, prompt: string, options: list<string>, correct_index: int}>  $already
     */
    private function prompt(string $document, array $already, int $round): string
    {
        $avoid = '';
        if ($already !== []) {
            $lines = array_map(
                fn (array $question) => '- '.$question['prompt'],
                array_slice($already, -12)
            );
            $avoid = "Do not repeat these questions:\n".implode("\n", $lines)."\n\n";
        }

        $head = $avoid.<<<'PROMPT'
Return JSON only, in this shape:
{"questions":[{"topic":"Community","prompt":"Short question?","options":["A","B","C","D"],"correct_index":0}]}

Rules:
- Write exactly 8 questions.
- Keep each prompt under 25 words and each option under 12 words.
- Exactly 4 options. correct_index is 0, 1, 2, or 3.
- Use 4 to 6 short topic names from the document.
- Ask only what the document says.

DOCUMENT:

PROMPT;

        $offset = $round === 0 ? 0 : (int) floor(mb_strlen($document) / 3);
        $slice = mb_substr($document, $offset);
        $room = 7400 - mb_strlen($head);

        return $head.mb_substr($slice, 0, max(400, $room));
    }
}
