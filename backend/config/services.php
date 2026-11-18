<?php

declare(strict_types=1);

return static function (Container $container): void {
    $container->instance(Container::class, $container);
    $container->singleton(Config::class, fn () => new Config(require __DIR__ . '/app.php'));

    $container->singleton(TokenService::class, function (Container $container): TokenService {
        $config = $container->get(Config::class);

        return new TokenService((string) $config->get('jwt.secret'), (int) $config->get('jwt.ttl'));
    });

    $container->alias(ListingRepositoryInterface::class, PdoListingRepository::class);
    $container->alias(CatalogRepositoryInterface::class, PdoCatalogRepository::class);
    $container->alias(UserRepositoryInterface::class, PdoUserRepository::class);
};
