<?php

declare(strict_types=1);

final class OptionalBearerAuthMiddleware extends BearerAuthMiddleware
{
    protected function required(): bool
    {
        return false;
    }
}
