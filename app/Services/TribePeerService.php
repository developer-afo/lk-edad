<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TribePeerService
{
    private string $baseUrl;

    private ?string $partnerToken = null;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('tribepeer.api_url'), '/');
    }

    public function authenticate(): string
    {
        if ($this->partnerToken) {
            return $this->partnerToken;
        }

        $cached = cache('edad_tribepeer_partner_token');
        if (is_string($cached) && $cached !== '') {
            $this->partnerToken = $cached;

            return $cached;
        }

        $response = Http::timeout(20)->post("{$this->baseUrl}/partner/v1/auth/token", [
            'client_id' => config('tribepeer.client_id'),
            'client_secret' => config('tribepeer.client_secret'),
            'scopes' => ['ai:chat'],
        ]);

        if (! $response->successful()) {
            Log::warning('TribePeer auth failed', ['status' => $response->status()]);
            throw new \RuntimeException('Could not reach TribePeer. Check the partner keys and try again.');
        }

        $token = $response->json('access_token') ?: $response->json('token');
        if (! is_string($token) || $token === '') {
            throw new \RuntimeException('TribePeer did not return an access token.');
        }

        $expiresIn = (int) $response->json('expires_in', 3500);
        cache(['edad_tribepeer_partner_token' => $token], now()->addSeconds(max(60, $expiresIn - 60)));
        $this->partnerToken = $token;

        return $token;
    }

    public function embedAuthInit(string $email, ?string $name = null, ?string $requestOrigin = null): array
    {
        $payload = [
            'publishable_key' => config('tribepeer.publishable_key'),
            'email' => $email,
        ];

        if ($name) {
            $payload['name'] = $name;
        }

        return $this->embedPost('/embed/v1/auth/init', $payload, $requestOrigin);
    }

    public function embedAuthVerify(string $email, string $otp, ?string $requestOrigin = null): array
    {
        return $this->embedPost('/embed/v1/auth/verify', [
            'publishable_key' => config('tribepeer.publishable_key'),
            'email' => $email,
            'otp' => $otp,
        ], $requestOrigin);
    }

    public function complete(string $system, string $user): string
    {
        try {
            $response = Http::withToken($this->authenticate())
                ->timeout(90)
                ->acceptJson()
                ->post("{$this->baseUrl}/partner/v1/ai/chat", [
                    'message' => $user,
                    'messages' => [
                        ['role' => 'system', 'content' => $system],
                        ['role' => 'user', 'content' => $user],
                    ],
                ]);
        } catch (\Throwable $e) {
            Log::warning('TribePeer chat failed', ['error' => $e->getMessage()]);
            throw new \RuntimeException('The quiz could not be prepared. Please try again.');
        }

        if (! $response->successful()) {
            Log::warning('TribePeer chat failed', [
                'status' => $response->status(),
                'message' => $response->json('message'),
            ]);
            throw new \RuntimeException('The quiz could not be prepared. Please try again.');
        }

        $reply = trim((string) ($response->json('reply') ?? ''));
        if ($reply === '') {
            throw new \RuntimeException('The quiz came back empty. Please try again.');
        }

        return $reply;
    }

    private function embedPost(string $path, array $payload, ?string $requestOrigin): array
    {
        try {
            $response = $this->embedHttp($requestOrigin)
                ->timeout(20)
                ->post($this->baseUrl.$path, $payload);
        } catch (\Throwable $e) {
            Log::warning('TribePeer embed call failed', ['path' => $path, 'error' => $e->getMessage()]);

            return ['message' => 'Sign-in is unavailable. Please try again shortly.'];
        }

        $json = $response->json();
        if (! is_array($json)) {
            return ['message' => 'Sign-in did not respond. Please try again.'];
        }

        if (! $response->successful() && empty($json['message'])) {
            $json['message'] = 'Could not complete sign-in. Check the email and try again.';
        }

        return $json;
    }

    private function embedHttp(?string $requestOrigin = null): PendingRequest
    {
        $origin = $this->resolveEmbedOrigin($requestOrigin);
        $request = Http::asJson()->acceptJson();

        if ($origin !== '') {
            $request = $request->withHeaders([
                'Origin' => $origin,
                'Referer' => $origin.'/',
            ]);
        }

        return $request;
    }

    private function resolveEmbedOrigin(?string $requestOrigin = null): string
    {
        foreach ([$requestOrigin, config('tribepeer.origin'), config('app.url')] as $candidate) {
            $origin = $this->normalizeOrigin((string) $candidate);
            if ($origin !== '') {
                return $origin;
            }
        }

        return '';
    }

    private function normalizeOrigin(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        if (! str_contains($value, '://')) {
            return rtrim(strtolower($value), '/');
        }

        $parts = parse_url($value);
        if (empty($parts['scheme']) || empty($parts['host'])) {
            return '';
        }

        $origin = strtolower($parts['scheme'].'://'.$parts['host']);
        if (! empty($parts['port'])) {
            $origin .= ':'.$parts['port'];
        }

        return $origin;
    }
}
