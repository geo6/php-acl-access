<?php

declare(strict_types=1);

namespace App\Handler\Admin;

use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerInterface;

class RolesHandlerFactory
{
    public function __invoke(ContainerInterface $container): RolesHandler
    {
        return new RolesHandler($container->get(TemplateRendererInterface::class));
    }
}
