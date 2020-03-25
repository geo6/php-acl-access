<?php

declare(strict_types=1);

namespace App\Handler\Admin;

use Laminas\Db\Sql\TableIdentifier;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerInterface;

class ResourcesHandlerFactory
{
    public function __invoke(ContainerInterface $container): ResourcesHandler
    {
        $config = $container->get('config');

        return new ResourcesHandler(
            $container->get(TemplateRendererInterface::class),
            new TableIdentifier($config['database']['tables']['resource'], $config['database']['schema']),
            new TableIdentifier($config['database']['tables']['role'], $config['database']['schema'])
        );
    }
}
