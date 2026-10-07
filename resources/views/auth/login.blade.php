@extends('layouts.app')

@section('title', 'Sign in · LK Edad')

@section('content')
    <div class="card">
        <h1>Sign in with TribePeer</h1>
        <p class="lead">Use the email on your TribePeer account. We’ll send a code. No separate password here.</p>
        <div id="alert" class="error" hidden></div>

        <form id="email-form">
            <label for="email">Email</label>
            <input id="email" type="email" name="email" required autocomplete="email" placeholder="you@example.com">
            <div id="name-wrap" hidden>
                <label for="name">Your name</label>
                <input id="name" type="text" name="name" autocomplete="name" placeholder="First and last name">
            </div>
            <button id="email-btn" type="submit">Send code</button>
        </form>

        <form id="otp-form" hidden>
            <label for="otp">6-digit code</label>
            <input id="otp" type="text" inputmode="numeric" maxlength="6" autocomplete="one-time-code" placeholder="000000">
            <button id="otp-btn" type="submit">Continue</button>
        </form>
    </div>
    <script>
        const token = document.querySelector('meta[name="csrf-token"]').content;
        const alertBox = document.getElementById('alert');
        const emailForm = document.getElementById('email-form');
        const otpForm = document.getElementById('otp-form');
        const nameWrap = document.getElementById('name-wrap');
        const emailInput = document.getElementById('email');
        const nameInput = document.getElementById('name');

        function showError(message) {
            alertBox.hidden = false;
            alertBox.textContent = message || 'Something went wrong. Please try again.';
        }

        async function post(url, body) {
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token,
                },
                body: JSON.stringify(body),
            });
            const data = await response.json().catch(() => ({}));
            if (!response.ok) {
                const message = data.message || (data.errors && Object.values(data.errors)[0]?.[0]);
                throw new Error(message || 'Please try again.');
            }
            return data;
        }

        emailForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            alertBox.hidden = true;
            const button = document.getElementById('email-btn');
            button.disabled = true;
            try {
                const data = await post(@json(route('auth.init')), {
                    email: emailInput.value.trim(),
                    name: nameInput.value.trim() || null,
                });
                if (data.status === 'name_required') {
                    nameWrap.hidden = false;
                    nameInput.required = true;
                    nameInput.focus();
                    showError('This email is new on TribePeer. Add your name, then send the code again.');
                    return;
                }
                emailForm.hidden = true;
                otpForm.hidden = false;
                document.getElementById('otp').focus();
            } catch (error) {
                showError(error.message);
            } finally {
                button.disabled = false;
            }
        });

        otpForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            alertBox.hidden = true;
            const button = document.getElementById('otp-btn');
            button.disabled = true;
            try {
                await post(@json(route('auth.verify')), {
                    email: emailInput.value.trim(),
                    otp: document.getElementById('otp').value.trim(),
                });
                window.location.href = @json(route('home'));
            } catch (error) {
                showError(error.message);
                button.disabled = false;
            }
        });
    </script>
@endsection
