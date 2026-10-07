<?php

namespace App\Http\Controllers;

use App\Models\QuizAnswer;
use App\Models\QuizSession;
use App\Models\User;
use App\Services\PdfTextExtractor;
use App\Services\QuizBuilder;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use InvalidArgumentException;

class QuizController extends Controller
{
    public function __construct(
        private readonly PdfTextExtractor $extractor,
        private readonly QuizBuilder $builder,
    ) {}

    public function store(Request $request)
    {
        $data = $request->validate([
            'pdf' => ['required', 'file', 'mimes:pdf', 'max:10240'],
        ]);

        /** @var User $user */
        $user = $request->attributes->get('edad_user');
        $file = $data['pdf'];
        $path = $file->store('quizzes/'.$user->id);

        $session = QuizSession::query()->create([
            'user_id' => $user->id,
            'pdf_original_name' => $file->getClientOriginalName(),
            'pdf_path' => $path,
            'status' => QuizSession::BUILDING,
        ]);

        set_time_limit(180);

        try {
            $absolute = storage_path('app/'.$path);
            $text = $this->extractor->extract($absolute);
            if ($text === null) {
                throw new InvalidArgumentException('We could not read that PDF. Use a file with selectable text, not a photo of a page.');
            }

            $this->builder->build($session, $text);
        } catch (InvalidArgumentException $e) {
            return $this->fail($request, $session, $e->getMessage());
        } catch (\Throwable $e) {
            report($e);

            return $this->fail($request, $session, 'The quiz could not be prepared. Please try again.');
        }

        if ($request->expectsJson()) {
            return response()->json(['redirect' => route('quiz.show')]);
        }

        return redirect()->route('quiz.show');
    }

    private function fail(Request $request, QuizSession $session, string $message)
    {
        $session->update([
            'status' => QuizSession::FAILED,
            'error' => $message,
        ]);

        if ($request->expectsJson()) {
            return response()->json(['message' => $message], 422);
        }

        return redirect()->route('home')->with('error', $message);
    }

    public function show(Request $request)
    {
        $session = $this->playable($request);
        $session->load(['questions.answer']);

        $next = $session->questions->first(fn ($question) => $question->answer === null);
        if (! $next) {
            $session->update(['status' => QuizSession::DONE]);

            return redirect()->route('quiz.results');
        }

        $answered = $session->questions->filter(fn ($question) => $question->answer !== null)->count();

        return view('quiz', [
            'session' => $session,
            'question' => $next,
            'number' => $answered + 1,
            'total' => $session->questions->count(),
        ]);
    }

    public function answer(Request $request)
    {
        $session = $this->playable($request);
        $data = $request->validate([
            'question_id' => ['required', 'integer'],
            'chosen_index' => ['required', 'integer', 'between:0,3'],
        ]);

        $question = $session->questions()->whereKey($data['question_id'])->first();
        if (! $question) {
            return redirect()->route('quiz.show');
        }

        QuizAnswer::query()->updateOrCreate(
            [
                'quiz_session_id' => $session->id,
                'quiz_question_id' => $question->id,
            ],
            ['chosen_index' => (int) $data['chosen_index']]
        );

        if ($session->status === QuizSession::READY) {
            $session->update(['status' => QuizSession::IN_PROGRESS]);
        }

        $remaining = $session->questions()->whereDoesntHave('answer')->count();
        if ($remaining === 0) {
            $session->update(['status' => QuizSession::DONE]);

            return redirect()->route('quiz.results');
        }

        return redirect()->route('quiz.show');
    }

    public function results(Request $request)
    {
        /** @var User $user */
        $user = $request->attributes->get('edad_user');

        $session = QuizSession::query()
            ->where('user_id', $user->id)
            ->where('status', QuizSession::DONE)
            ->latest('id')
            ->first();

        if (! $session) {
            return redirect()->route('home')->with('error', 'Finish a quiz to see your strengths.');
        }

        return view('results', [
            'session' => $session,
            'score' => $session->scorecard(),
        ]);
    }

    private function playable(Request $request): QuizSession
    {
        /** @var User $user */
        $user = $request->attributes->get('edad_user');

        $session = QuizSession::query()
            ->where('user_id', $user->id)
            ->whereIn('status', [QuizSession::READY, QuizSession::IN_PROGRESS])
            ->latest('id')
            ->first();

        if (! $session) {
            throw new HttpResponseException(
                redirect()->route('home')->with('error', 'Upload a PDF to start a quiz.')
            );
        }

        return $session;
    }
}
