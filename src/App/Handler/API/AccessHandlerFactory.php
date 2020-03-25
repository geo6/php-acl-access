<?php

declare(strict_types=1);

namespace App\Handler\API;

use Laminas\Db\Sql\TableIdentifier;
use Psr\Container\ContainerInterface;

class AccessHandlerFactory
{
    public function __invoke(ContainerInterface $container): AccessHandler
    {
        $config = $container->get('config');

        return new AccessHandler(
            new TableIdentifier($config['database']['tables']['resource'], $config['database']['schema']),
            new TableIdentifier($config['database']['tables']['role'], $config['database']['schema']),
            new TableIdentifier($config['database']['tables']['role_resource'], $config['database']['schema'])
        );
    }
}
