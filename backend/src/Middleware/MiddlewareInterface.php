<?php

declare(strict_types=1);

interface MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response;
}
