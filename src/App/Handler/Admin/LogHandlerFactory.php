<?php

declare(strict_types=1);

namespace App\Handler\Admin;

use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerInterface;

class LogHandlerFactory
{
    public function __invoke(ContainerInterface $container): LogHandler
    {
        $config = $container->get('config');

        return new LogHandler(
            $container->get(TemplateRendererInterface::class),
            $config['logs'] ?? []
        );
    }
}
