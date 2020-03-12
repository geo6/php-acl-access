<?php

declare(strict_types=1);

namespace App\Handler\Admin;

use Psr\Container\ContainerInterface;
use Mezzio\Template\TemplateRendererInterface;

class LogHandlerFactory
{
    public function __invoke(ContainerInterface $container): LogHandler
    {
        return new LogHandler($container->get(TemplateRendererInterface::class));
    }
}
