@extends('layouts.app')

@section('title', 'Upload · LK Edad')

@section('content')
    <div class="card">
        <h1>What do you already know?</h1>
        <p class="lead">Upload one PDF. We’ll turn it into a short quiz, then show where you are strong and where the document still has gaps.</p>

        @if ($errors->any())
            <div class="error">{{ $errors->first() }}</div>
        @endif

        @if ($active)
            <p>You have a quiz in progress from <strong>{{ $active->pdf_original_name }}</strong>.</p>
            <p class="row">
                <a class="btn" href="{{ route('quiz.show') }}">Continue quiz</a>
                <a class="btn ghost" href="{{ route('home', ['new' => 1]) }}">Upload a different document</a>
            </p>
        @else
            <form id="upload-form" method="post" action="{{ route('quiz.store') }}" enctype="multipart/form-data">
                @csrf
                <div id="upload-fields">
                    <label for="pdf">PDF document</label>
                    <input id="pdf" type="file" name="pdf" accept="application/pdf,.pdf" required>
                    <button id="build-btn" type="submit">Build quiz</button>
                    <p class="muted">Use a text PDF, not a scanned image. 10 MB max.</p>
                </div>
                <div id="processing" class="processing" hidden>
                    <div class="spinner" aria-hidden="true"></div>
                    <div>
                        <strong>Processing document…</strong>
                        <p class="muted" style="margin: 4px 0 0;">Reading the PDF and writing your quiz. Stay on this page.</p>
                    </div>
                </div>
                <div id="upload-error" class="error" hidden></div>
            </form>
            <script>
                const form = document.getElementById('upload-form');
                const fields = document.getElementById('upload-fields');
                const processing = document.getElementById('processing');
                const errorBox = document.getElementById('upload-error');
                form.addEventListener('submit', async (event) => {
                    event.preventDefault();
                    errorBox.hidden = true;
                    fields.hidden = true;
                    processing.hidden = false;
                    try {
                        const response = await fetch(form.action, {
                            method: 'POST',
                            headers: { 'Accept': 'application/json' },
                            body: new FormData(form),
                        });
                        const data = await response.json().catch(() => ({}));
                        if (!response.ok) {
                            const message = data.message
                                || (data.errors && Object.values(data.errors)[0]?.[0])
                                || 'The quiz could not be prepared. Please try again.';
                            throw new Error(message);
                        }
                        window.location.href = data.redirect || @json(route('quiz.show'));
                    } catch (error) {
                        processing.hidden = true;
                        fields.hidden = false;
                        errorBox.hidden = false;
                        errorBox.textContent = error.message || 'The quiz could not be prepared. Please try again.';
                    }
                });
            </script>
        @endif
    </div>

    @if ($lastDone)
        <p class="muted" style="margin-top: 16px;">
            Last result: {{ $lastDone->pdf_original_name }}.
            <a href="{{ route('quiz.results') }}">View strengths</a>
        </p>
    @endif
@endsection
