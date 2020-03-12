<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Permissions;
use Mezzio\Authentication\AuthenticationInterface;
use Mezzio\Authentication\Exception\InvalidConfigException;
use Mezzio\Router\RouterInterface;
use Psr\Container\ContainerInterface;

/**
 * @see \Mezzio\Authentication\AuthenticationMiddlewareFactory
 */
class AccessMiddlewareFactory
{
    public function __invoke(ContainerInterface $container): AccessMiddleware
    {
        $config = $container->get('config');

        $authentication = $container->has(AuthenticationInterface::class) ?
            $container->get(AuthenticationInterface::class) : null;

        if (isset($config['authentication']['pdo']) && null === $authentication) {
            throw new InvalidConfigException(
                'AuthenticationInterface service is missing'
            );
        }

        $router = $container->get(RouterInterface::class);
        $redirect = $router->generateUri($config['authentication']['redirect']);

        $acl = new Permissions($config['authentication']['pdo'] ?? null, $config['authorization']);

        return new AccessMiddleware($authentication, $redirect, $acl);
    }
}
