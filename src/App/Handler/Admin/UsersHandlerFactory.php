<?php

declare(strict_types=1);

namespace App\Handler\Admin;

use Laminas\Db\Sql\TableIdentifier;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerInterface;

class UsersHandlerFactory
{
    public function __invoke(ContainerInterface $container): UsersHandler
    {
        $config = $container->get('config');

        return new UsersHandler(
            $container->get(TemplateRendererInterface::class),
            new TableIdentifier($config['database']['tables']['role'], $config['database']['schema']),
            new TableIdentifier($config['database']['tables']['user'], $config['database']['schema']),
            new TableIdentifier($config['database']['tables']['user_role'], $config['database']['schema']),
        );
    }
}
