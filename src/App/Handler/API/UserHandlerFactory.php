<?php

declare(strict_types=1);

namespace App\Handler\API;

use Laminas\Db\Sql\TableIdentifier;
use Psr\Container\ContainerInterface;

class UserHandlerFactory
{
    public function __invoke(ContainerInterface $container): UserHandler
    {
        $config = $container->get('config');

        return new UserHandler(
            new TableIdentifier($config['database']['tables']['user'], $config['database']['schema']),
            new TableIdentifier($config['database']['tables']['role'], $config['database']['schema']),
            new TableIdentifier($config['database']['tables']['user_role'], $config['database']['schema'])
        );
    }
}
