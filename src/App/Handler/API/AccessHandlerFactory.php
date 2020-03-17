<?php

declare(strict_types=1);

namespace App\Handler\API;

use Psr\Container\ContainerInterface;

class AccessHandlerFactory
{
    public function __invoke(ContainerInterface $container): AccessHandler
    {
        $config = $container->get('config');

        return new AccessHandler($config['tables']['role_resource'], $config['tables']['resource'], $config['tables']['role']);
    }
}
