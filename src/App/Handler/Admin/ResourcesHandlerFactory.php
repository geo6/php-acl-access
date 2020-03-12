<?php

declare(strict_types=1);

namespace App\Handler\Admin;

use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerInterface;

class ResourcesHandlerFactory
{
    public function __invoke(ContainerInterface $container): ResourcesHandler
    {
        return new ResourcesHandler($container->get(TemplateRendererInterface::class));
    }
}
