<?php

namespace App\Services;

class JwtService
{
    public function make(array $claims, ?int $ttlMinutes = null): string
    {
        $now = time();
        $ttl = $ttlMinutes ?? (int) env('JWT_TTL', 120);

        $payload = array_merge($claims, [
            'iat' => $now,
            'exp' => $now + ($ttl * 60),
        ]);

        $header = ['typ' => 'JWT', 'alg' => 'HS256'];
        $segments = [
            $this->base64UrlEncode(json_encode($header)),
            $this->base64UrlEncode(json_encode($payload)),
        ];
        $segments[] = $this->signature($segments[0] . '.' . $segments[1]);

        return implode('.', $segments);
    }

    public function verify(string $token): ?array
    {
        $segments = explode('.', $token);

        if (count($segments) !== 3) {
            return null;
        }

        [$header, $payload, $signature] = $segments;
        $expected = $this->signature($header . '.' . $payload);

        if (!hash_equals($expected, $signature)) {
            return null;
        }

        $claims = json_decode($this->base64UrlDecode($payload), true);

        if (!is_array($claims) || (isset($claims['exp']) && time() >= (int) $claims['exp'])) {
            return null;
        }

        return $claims;
    }

    private function signature(string $value): string
    {
        return $this->base64UrlEncode(hash_hmac('sha256', $value, $this->secret(), true));
    }

    private function secret(): string
    {
        $secret = (string) env('JWT_SECRET', config('app.key'));

        if (str_starts_with($secret, 'base64:')) {
            return base64_decode(substr($secret, 7)) ?: $secret;
        }

        return $secret;
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $value): string
    {
        return base64_decode(strtr($value, '-_', '+/')) ?: '';
    }
}

