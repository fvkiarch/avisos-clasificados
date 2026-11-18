<?php

declare(strict_types=1);

date_default_timezone_set('UTC');
require dirname(__DIR__) . '/src/autoload.php';

$container = new Container();
(require dirname(__DIR__) . '/config/services.php')($container);
(require dirname(__DIR__) . '/config/routes.php')($container->get(Router::class));

$container->get(Application::class)->handle(Request::capture())->send();
