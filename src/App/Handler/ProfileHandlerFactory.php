<?php

declare(strict_types=1);

namespace App\Handler;

use Laminas\Db\Sql\TableIdentifier;
use Mezzio\Router\RouterInterface;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerInterface;

class ProfileHandlerFactory
{
    public function __invoke(ContainerInterface $container): ProfileHandler
    {
        $config = $container->get('config');

        return new ProfileHandler(
            $container->get(TemplateRendererInterface::class),
            $container->get(RouterInterface::class),
            new TableIdentifier($config['database']['tables']['resource'], $config['database']['schema']),
            $config['authentication']['pdo']
        );
    }
}
