@extends('layouts.app')

@section('title', 'Quiz · LK Edad')

@section('content')
    <p class="muted">Question {{ $number }} of {{ $total }} · {{ $session->pdf_original_name }}</p>
    <div class="card">
        <h1 style="font-size: 1.35rem;">{{ $question->prompt }}</h1>
        <form method="post" action="{{ route('quiz.answer') }}">
            @csrf
            <input type="hidden" name="question_id" value="{{ $question->id }}">
            @foreach ($question->options as $index => $option)
                <button class="option" type="submit" name="chosen_index" value="{{ $index }}">
                    {{ $option }}
                </button>
            @endforeach
        </form>
    </div>
@endsection
