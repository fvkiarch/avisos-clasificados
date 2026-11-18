<?php

declare(strict_types=1);

class BearerAuthMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly TokenService $tokens, private readonly CurrentUser $current)
    {
    }

    protected function required(): bool
    {
        return true;
    }

    public function handle(Request $request, callable $next): Response
    {
        $token = $request->bearerToken();

        if ($token === null) {
            if ($this->required()) {
                throw new UnauthorizedException('Falta el token Bearer');
            }

            return $next($request);
        }
        $user = $this->tokens->verify($token);

        if ($user === null) {
            throw new UnauthorizedException('Token inválido o expirado');
        }
        $this->current->set($user);

        return $next($request);
    }
}
