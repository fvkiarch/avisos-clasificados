<?php

declare(strict_types=1);

return static function (Router $router): void {
    $router->group('/api/v1', [], function (Router $router): void {
        $router->post('/auth/login', [AuthController::class, 'login']);
        
        $router->group('', [BearerAuthMiddleware::class], function (Router $router): void {
            $router->get('/listings/mine', [ListingController::class, 'mine']);
        });

        $router->group('', [OptionalBearerAuthMiddleware::class], function (Router $router): void {
            $router->get('/catalog', [CatalogController::class, 'index']);
            $router->get('/stats', [StatsController::class, 'index']);
            $router->get('/listings', [ListingController::class, 'index']);
            $router->get('/listings/{id}', [ListingController::class, 'show']);
        });

		$router->group('', [BearerAuthMiddleware::class], function (Router $router): void {
			$router->get('/auth/me', [AuthController::class, 'me']);
			$router->post('/listings', [ListingController::class, 'store']);
			$router->delete('/listings/{id}', [ListingController::class, 'destroy']);
			$router->get('/users', [UserController::class, 'index']);
			$router->post('/users', [UserController::class, 'store']);
			$router->delete('/users/{id}', [UserController::class, 'destroy']);
		});
    });
};
