<?php

declare(strict_types=1);

final class TokenService
{
    public function __construct(private readonly string $secret, private readonly int $ttl = 3600)
    {
        if (strlen($secret) < 32) {
            throw new InvalidArgumentException('JWT_SECRET tiene que tener 32 caracteres al menos');
        }
    }

    public function ttl(): int
    {
        return $this->ttl;
    }

    public function issue(AuthUser $u): string
    {
        $now = time();
        $payload = ['sub' => $u->id, 'name' => $u->name, 'role' => $u->role, 'perms' => $u->permissions,
                    'iat' => $now, 'exp' => $now + $this->ttl];
        $data = $this->b64(json_encode(['alg' => 'HS256', 'typ' => 'JWT'])) . '.' . $this->b64(json_encode($payload));

        return $data . '.' . $this->b64(hash_hmac('sha256', $data, $this->secret, true));
    }

    public function verify(string $token): ?AuthUser
    {
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            return null;
        }
        [$h, $p, $s] = $parts;

        if (!hash_equals($this->b64(hash_hmac('sha256', "$h.$p", $this->secret, true)), $s)) {
            return null;
        }
        $header = json_decode((string) base64_decode(strtr($h, '-_', '+/')), true);
        $payload = json_decode((string) base64_decode(strtr($p, '-_', '+/')), true);

        if (!is_array($header) || ($header['alg'] ?? '') !== 'HS256' || !is_array($payload)) {
            return null;
        }

        if (($payload['exp'] ?? 0) < time()) {
            return null;
        }

        return new AuthUser((int) $payload['sub'], (string) $payload['name'], (string) $payload['role'], (array) $payload['perms']);
    }

    private function b64(string $s): string
    {
        return rtrim(strtr(base64_encode($s), '+/', '-_'), '=');
    }
}
