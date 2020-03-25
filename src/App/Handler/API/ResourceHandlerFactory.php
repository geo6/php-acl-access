<?php

declare(strict_types=1);

namespace App\Handler\API;

use Laminas\Db\Sql\TableIdentifier;
use Psr\Container\ContainerInterface;

class ResourceHandlerFactory
{
    public function __invoke(ContainerInterface $container): ResourceHandler
    {
        $config = $container->get('config');

        return new ResourceHandler(
            new TableIdentifier($config['database']['tables']['resource'], $config['database']['schema'])
        );
    }
}
