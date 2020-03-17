<?php

declare(strict_types=1);

namespace App\Handler\API;

use Psr\Container\ContainerInterface;

class RoleHandlerFactory
{
    public function __invoke(ContainerInterface $container): RoleHandler
    {
        $config = $container->get('config');

        return new RoleHandler($config['tables']['role']);
    }
}
