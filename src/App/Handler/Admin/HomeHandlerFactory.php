<?php

declare(strict_types=1);

namespace App\Handler\Admin;

use Psr\Container\ContainerInterface;
use Mezzio\Template\TemplateRendererInterface;

class HomeHandlerFactory
{
    public function __invoke(ContainerInterface $container): HomeHandler
    {
        return new HomeHandler($container->get(TemplateRendererInterface::class));
    }
}
