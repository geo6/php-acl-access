<?php

declare(strict_types=1);

namespace App\Handler\API;

use Laminas\Db\Sql\TableIdentifier;
use Psr\Container\ContainerInterface;

class RoleHandlerFactory
{
    public function __invoke(ContainerInterface $container): RoleHandler
    {
        $config = $container->get('config');

        return new RoleHandler(
            new TableIdentifier($config['database']['tables']['role'], $config['database']['schema'])
        );
    }
}
