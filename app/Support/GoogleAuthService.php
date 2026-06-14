<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class GoogleAuthService
{
    public static function resolveProfile(array $payload): array
    {
        if (!empty($payload['credential'])) {
            return self::verifyIdToken($payload['credential']);
        }

        if (!empty($payload['id_token'])) {
            return self::verifyIdToken($payload['id_token']);
        }

        if (!empty($payload['code'])) {
            return self::exchangeAuthCode(
                $payload['code'],
                $payload['redirectUri'] ?? $payload['redirect_uri'] ?? null,
            );
        }

        throw new RuntimeException('Google auth requires code, credential, or id_token.');
    }

    public static function verifyIdToken(string $idToken): array
    {
        $response = Http::timeout(10)->get('https://oauth2.googleapis.com/tokeninfo', [
            'id_token' => $idToken,
        ]);

        if (!$response->ok()) {
            throw new RuntimeException('Invalid Google ID token.');
        }

        $data = $response->json();
        self::assertClientId($data['aud'] ?? null);

        if (empty($data['email'])) {
            throw new RuntimeException('Google account email is required.');
        }

        if (($data['email_verified'] ?? 'false') !== 'true') {
            throw new RuntimeException('Google email is not verified.');
        }

        return [
            'google_id' => $data['sub'],
            'email' => Str::lower($data['email']),
            'name' => $data['name'] ?? $data['email'],
            'avatar' => $data['picture'] ?? null,
        ];
    }

    public static function exchangeAuthCode(string $code, ?string $redirectUri): array
    {
        $redirectUri = $redirectUri ?: config('services.google.redirect_uri');

        $response = Http::asForm()->timeout(10)->post('https://oauth2.googleapis.com/token', [
            'code' => $code,
            'client_id' => config('services.google.client_id'),
            'client_secret' => config('services.google.client_secret'),
            'redirect_uri' => $redirectUri,
            'grant_type' => 'authorization_code',
        ]);

        if (!$response->ok()) {
            throw new RuntimeException('Google auth code exchange failed.');
        }

        $idToken = $response->json('id_token');

        if (!$idToken) {
            throw new RuntimeException('Google token response missing id_token.');
        }

        return self::verifyIdToken($idToken);
    }

    private static function assertClientId(?string $audience): void
    {
        $clientId = config('services.google.client_id');

        if (!$clientId || $audience !== $clientId) {
            throw new RuntimeException('Google token client mismatch.');
        }
    }
}
