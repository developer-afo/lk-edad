<?php

namespace App\Support;

use InvalidArgumentException;

class QuizPaper
{
    /**
     * @return list<array{topic: string, prompt: string, options: list<string>, correct_index: int}>
     */
    public static function parse(string $reply): array
    {
        $clean = [];
        foreach (self::rawQuestions($reply) as $question) {
            $row = self::question($question);
            if ($row !== null) {
                $clean[] = $row;
            }
        }

        return $clean;
    }

    public static function fromModelReply(string $reply): array
    {
        return self::finalize(self::parse($reply));
    }

    /**
     * @param  list<array{topic: string, prompt: string, options: list<string>, correct_index: int}>  $questions
     * @return list<array{topic: string, prompt: string, options: list<string>, correct_index: int}>
     */
    public static function finalize(array $questions): array
    {
        $questions = self::capTopics($questions);

        if (count($questions) < 10) {
            throw new InvalidArgumentException('That document did not produce enough questions. Try a longer PDF with more text.');
        }

        return array_slice($questions, 0, 25);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function rawQuestions(string $reply): array
    {
        $found = [];
        $text = trim($reply);
        if (preg_match('/```(?:json)?\s*(.*?)```/s', $text, $match)) {
            $text = trim($match[1]);
        }

        $decoded = self::decodeObject($text);
        if (is_array($decoded)) {
            $list = $decoded['questions'] ?? $decoded['items'] ?? null;
            if (is_array($list)) {
                foreach ($list as $question) {
                    if (is_array($question)) {
                        $found[] = $question;
                    }
                }
            }
        }

        if ($found !== []) {
            return $found;
        }

        return self::salvageObjects($text);
    }

    private static function decodeObject(string $text): ?array
    {
        $start = strpos($text, '{');
        $end = strrpos($text, '}');
        if ($start === false || $end === false || $end <= $start) {
            return null;
        }

        $decoded = json_decode(substr($text, $start, $end - $start + 1), true);

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * Pull out every complete question object, even when the model stops mid-JSON.
     *
     * @return list<array<string, mixed>>
     */
    private static function salvageObjects(string $text): array
    {
        $found = [];
        $length = strlen($text);
        $inString = false;
        $escape = false;

        for ($i = 0; $i < $length; $i++) {
            $char = $text[$i];
            if ($inString) {
                if ($escape) {
                    $escape = false;

                    continue;
                }
                if ($char === '\\') {
                    $escape = true;

                    continue;
                }
                if ($char === '"') {
                    $inString = false;
                }

                continue;
            }

            if ($char === '"') {
                $inString = true;

                continue;
            }

            if ($char !== '{') {
                continue;
            }

            $end = self::matchingBrace($text, $i);
            if ($end === null) {
                continue;
            }

            $decoded = json_decode(substr($text, $i, $end - $i + 1), true);
            $i = $end;
            if (! is_array($decoded)) {
                continue;
            }

            if (isset($decoded['questions']) && is_array($decoded['questions'])) {
                foreach ($decoded['questions'] as $question) {
                    if (is_array($question)) {
                        $found[] = $question;
                    }
                }

                continue;
            }

            if (isset($decoded['options']) || isset($decoded['choices']) || isset($decoded['prompt']) || isset($decoded['question'])) {
                $found[] = $decoded;
            }
        }

        return $found;
    }

    private static function matchingBrace(string $text, int $start): ?int
    {
        $depth = 0;
        $inString = false;
        $escape = false;
        $length = strlen($text);

        for ($i = $start; $i < $length; $i++) {
            $char = $text[$i];
            if ($inString) {
                if ($escape) {
                    $escape = false;

                    continue;
                }
                if ($char === '\\') {
                    $escape = true;

                    continue;
                }
                if ($char === '"') {
                    $inString = false;
                }

                continue;
            }

            if ($char === '"') {
                $inString = true;

                continue;
            }
            if ($char === '{') {
                $depth++;
            }
            if ($char === '}') {
                $depth--;
                if ($depth === 0) {
                    return $i;
                }
            }
        }

        return null;
    }

    /**
     * @return array{topic: string, prompt: string, options: list<string>, correct_index: int}|null
     */
    private static function question(mixed $question): ?array
    {
        if (! is_array($question)) {
            return null;
        }

        $prompt = trim((string) ($question['prompt'] ?? $question['question'] ?? $question['text'] ?? ''));
        $topic = self::topicName((string) ($question['topic'] ?? $question['category'] ?? 'General'));
        $options = $question['options'] ?? $question['choices'] ?? null;

        if ($prompt === '' || $topic === '' || ! is_array($options)) {
            return null;
        }

        $labels = [];
        foreach ($options as $option) {
            if (is_array($option)) {
                $label = trim((string) ($option['text'] ?? $option['label'] ?? ''));
            } else {
                $label = trim((string) $option);
            }
            if ($label === '') {
                continue;
            }
            $labels[] = $label;
        }

        $labels = array_slice($labels, 0, 4);
        if (count($labels) !== 4) {
            return null;
        }

        $index = self::correctIndex($question['correct_index'] ?? $question['answer'] ?? null, $labels);
        if ($index === null) {
            return null;
        }

        return [
            'topic' => $topic,
            'prompt' => $prompt,
            'options' => $labels,
            'correct_index' => $index,
        ];
    }

    private static function correctIndex(mixed $correct, array $labels): ?int
    {
        if (is_int($correct) || (is_string($correct) && preg_match('/^[0-3]$/', trim($correct)))) {
            return (int) $correct;
        }

        $text = trim((string) $correct);
        if ($text === '') {
            return null;
        }

        $letter = strtoupper($text[0]);
        if (in_array($letter, ['A', 'B', 'C', 'D'], true) && strlen($text) === 1) {
            return ord($letter) - ord('A');
        }

        foreach ($labels as $index => $label) {
            if (strcasecmp($label, $text) === 0) {
                return $index;
            }
        }

        return null;
    }

    private static function topicName(string $topic): string
    {
        $topic = trim(preg_replace('/\s+/', ' ', $topic) ?? '');
        if ($topic === '') {
            return '';
        }

        return mb_substr($topic, 0, 40);
    }

    /**
     * Keep the 8 topics that appear most often. Drop the rest.
     *
     * @param  list<array{topic: string, prompt: string, options: list<string>, correct_index: int}>  $questions
     * @return list<array{topic: string, prompt: string, options: list<string>, correct_index: int}>
     */
    private static function capTopics(array $questions): array
    {
        $counts = [];
        foreach ($questions as $question) {
            $key = mb_strtolower($question['topic']);
            $counts[$key]['name'] = $question['topic'];
            $counts[$key]['count'] = ($counts[$key]['count'] ?? 0) + 1;
        }

        uasort($counts, function (array $a, array $b) {
            return $b['count'] <=> $a['count'];
        });

        $kept = array_slice($counts, 0, 8, true);
        $names = [];
        foreach ($kept as $key => $row) {
            $names[$key] = $row['name'];
        }

        $fallback = $names === [] ? 'General' : reset($names);
        $filtered = [];
        foreach ($questions as $question) {
            $key = mb_strtolower($question['topic']);
            $question['topic'] = $names[$key] ?? $fallback;
            $filtered[] = $question;
        }

        return $filtered;
    }
}
