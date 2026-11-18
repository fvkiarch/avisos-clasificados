<?php

declare(strict_types=1);

final class AuthController
{
    public function __construct(
        private readonly AuthService $auth,
        private readonly TokenService $tokens,
        private readonly CurrentUser $current,
    ) {
    }

    public function login(Request $request): Response
    {
        $email = trim((string) $request->input('email', ''));
        $password = (string) $request->input('password', '');

        if ($email === '' || $password === '') {
            throw new ValidationException(['email' => 'Email y password son obligatorios.']);
        }
        $user = $this->auth->attempt($email, $password) ?? throw new UnauthorizedException('Credenciales inválidas');

        return Response::json([
            'token' => $this->tokens->issue($user), 'token_type' => 'Bearer',
            'expires_in' => $this->tokens->ttl(), 'user' => $user->toArray(),
        ]);
    }

    public function me(): Response
    {
        return Response::json(['data' => $this->current->user()?->toArray()]);
    }
}
