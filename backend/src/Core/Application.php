<?php

declare(strict_types=1);

final class Application
{
    public function __construct(
        private readonly Container $container,
        private readonly Router $router,
        private readonly ErrorHandler $errors,
        private readonly Cors $cors,
    ) {
    }

    public function handle(Request $request): Response
    {
        $this->container->instance(Request::class, $request);

        try {
            $response = $request->method() === 'OPTIONS' ? new Response('', 204) : $this->router->dispatch($request);
        } catch (Throwable $e) {
            $response = $this->errors->render($e);
        }
        $response = $response->withHeader('X-Content-Type-Options', 'nosniff');

        foreach ($this->cors->headersFor($request) as $name => $value) {
            $response = $response->withHeader($name, $value);
        }

        return $response;
    }
}
