<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Permissions;
use Laminas\Db\Sql\TableIdentifier;
use Mezzio\Authentication\AuthenticationInterface;
use Mezzio\Authentication\Exception\InvalidConfigException;
use Mezzio\Router\RouterInterface;
use Mezzio\Template\TemplateRendererInterface;
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

        $acl = new Permissions($config['authentication']['pdo'] ?? null, $config['authorization'], $config['database']['tables']);

        return new AccessMiddleware(
            $authentication,
            $redirect,
            $acl,
            new TableIdentifier($config['database']['tables']['resource'], $config['database']['schema']),
            $container->get(TemplateRendererInterface::class)
        );
    }
}
