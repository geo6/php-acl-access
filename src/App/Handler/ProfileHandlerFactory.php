<?php

declare(strict_types=1);

namespace App\Handler;

use Laminas\Db\Sql\TableIdentifier;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerInterface;

class ProfileHandlerFactory
{
    public function __invoke(ContainerInterface $container): ProfileHandler
    {
        $config = $container->get('config');

        $table = new TableIdentifier($config['database']['tables']['resource'], $config['database']['schema']);

        return new ProfileHandler(
            $container->get(TemplateRendererInterface::class),
            $table
        );
    }
}
