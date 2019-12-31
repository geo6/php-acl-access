<?php

declare(strict_types=1);

namespace App\Middleware;

use Psr\Container\ContainerInterface;

class DbMiddlewareFactory
{
    public function __invoke(ContainerInterface $container): DbMiddleware
    {
        $config = $container->get('config');

        if (isset($config['authentication']['pdo'])) {
            $pdo = $config['authentication']['pdo'];
        } else {
            $pdo = [];
        }

        return new DbMiddleware($pdo);
    }
}
