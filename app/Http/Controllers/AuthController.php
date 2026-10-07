<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\TribePeerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(private readonly TribePeerService $tribepeer) {}

    public function showLogin()
    {
        if (session('user_id') && User::query()->find(session('user_id'))) {
            return redirect()->route('home');
        }

        return view('auth.login');
    }

    public function init(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'name' => ['nullable', 'string', 'max:120'],
        ]);

        $name = trim((string) ($data['name'] ?? ''));
        $result = $this->tribepeer->embedAuthInit(
            $data['email'],
            $name !== '' ? $name : null,
            $request->headers->get('Origin')
        );

        $status = $result['status'] ?? null;

        if ($status === 'otp_sent') {
            session([
                'login_email' => $data['email'],
                'login_name' => $name !== '' ? $name : session('login_name'),
            ]);

            return response()->json(['status' => 'otp_sent']);
        }

        if ($status === 'name_required' || ! empty($result['requires_name'])) {
            session(['login_email' => $data['email']]);

            return response()->json(['status' => 'name_required']);
        }

        return response()->json([
            'message' => $this->signInError($result['message'] ?? null, 'Could not send the code. Please try again.'),
        ], 422);
    }

    public function verify(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'otp' => ['required', 'string', 'size:6'],
        ]);

        $result = $this->tribepeer->embedAuthVerify(
            $data['email'],
            $data['otp'],
            $request->headers->get('Origin')
        );

        if (($result['status'] ?? null) !== 'verified' || empty($result['access_token'])) {
            return response()->json([
                'message' => $this->signInError($result['message'] ?? null, 'That code was not accepted. Please try again.'),
            ], 422);
        }

        $user = User::query()->firstOrNew(['email' => $data['email']]);
        $user->email_verified_at = now();
        $user->embed_token = $result['access_token'];
        $user->embed_token_expires_at = now()->addSeconds((int) ($result['expires_in'] ?? 3600));
        $user->name = $result['user']['name'] ?? $user->name ?? session('login_name');
        $user->save();

        session([
            'user_id' => $user->id,
        ]);
        session()->forget(['login_email', 'login_name']);

        return response()->json(['status' => 'verified']);
    }

    public function logout(Request $request)
    {
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function signInError(?string $message, string $fallback): string
    {
        $message = trim((string) $message);
        $lower = strtolower($message);

        if ($message === '') {
            return $fallback;
        }

        if (str_contains($lower, 'publishable') || str_contains($lower, 'client') || str_contains($lower, 'origin')) {
            return 'Sign-in is not set up for this site yet. Add this app’s address to the TribePeer key, then try again.';
        }

        return $message;
    }
}
