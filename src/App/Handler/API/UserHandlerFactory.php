<?php

declare(strict_types=1);

namespace App\Handler\API;

use Psr\Container\ContainerInterface;

class UserHandlerFactory
{
    public function __invoke(ContainerInterface $container): UserHandler
    {
        $config = $container->get('config');

        return new UserHandler($config['tables']['user'], $config['tables']['role'], $config['tables']['user_role']);
    }
}
