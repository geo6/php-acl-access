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

        $router = $container->get(RouterInterface::class);
        $redirect = $router->generateUri($config['authentication']['redirect']);

        return new AccessMiddleware(
            $authentication,
            $redirect,
            $config['authorization'],
            new TableIdentifier($config['database']['tables']['resource'], $config['database']['schema']),
            new TableIdentifier($config['database']['tables']['role'], $config['database']['schema']),
            new TableIdentifier($config['database']['tables']['user'], $config['database']['schema']),
            new TableIdentifier($config['database']['tables']['role_resource'], $config['database']['schema']),
            new TableIdentifier($config['database']['tables']['user_role'], $config['database']['schema']),
            $container->get(TemplateRendererInterface::class)
        );
    }
}
