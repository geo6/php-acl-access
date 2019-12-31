<?php

declare(strict_types=1);

namespace App\Handler\Admin;

use Psr\Container\ContainerInterface;
use Zend\Expressive\Template\TemplateRendererInterface;

class ResourcesHandlerFactory
{
    public function __invoke(ContainerInterface $container): ResourcesHandler
    {
        return new ResourcesHandler($container->get(TemplateRendererInterface::class));
    }
}
