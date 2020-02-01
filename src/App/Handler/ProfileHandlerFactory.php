<?php

declare(strict_types=1);

namespace App\Handler;

use Psr\Container\ContainerInterface;
use Zend\Expressive\Template\TemplateRendererInterface;

class ProfileHandlerFactory
{
    public function __invoke(ContainerInterface $container): ProfileHandler
    {
        return new ProfileHandler($container->get(TemplateRendererInterface::class));
    }
}
