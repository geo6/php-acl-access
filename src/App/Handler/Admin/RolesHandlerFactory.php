<?php

declare(strict_types=1);

namespace App\Handler\Admin;

use Psr\Container\ContainerInterface;
use Zend\Expressive\Template\TemplateRendererInterface;

class RolesHandlerFactory
{
    public function __invoke(ContainerInterface $container): RolesHandler
    {
        return new RolesHandler($container->get(TemplateRendererInterface::class));
    }
}
