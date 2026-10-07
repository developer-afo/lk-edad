@extends('layouts.app')

@section('title', 'Strengths · LK Edad')

@section('content')
    <div class="card">
        <p class="muted">{{ $session->pdf_original_name }}</p>
        <p class="overall">{{ $score['overall'] }}%</p>
        <p class="lead">{{ $score['correct'] }} of {{ $score['total'] }} correct. Higher bars are the parts of the document you know better.</p>

        @foreach ($score['topics'] as $topic)
            <div class="bar-row">
                <div class="bar-meta">
                    <strong>{{ $topic['topic'] }}</strong>
                    <span>{{ $topic['percent'] }}%</span>
                </div>
                <div class="track">
                    <div class="fill" style="width: {{ $topic['percent'] }}%"></div>
                </div>
                <p class="muted">{{ $topic['correct'] }} of {{ $topic['total'] }}</p>
            </div>
        @endforeach

        <p class="row">
            <a class="btn" href="{{ route('home', ['new' => 1]) }}">Start new session</a>
        </p>
    </div>
@endsection