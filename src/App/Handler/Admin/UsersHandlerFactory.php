<?php

declare(strict_types=1);

namespace App\Handler\Admin;

use Psr\Container\ContainerInterface;
use Zend\Expressive\Template\TemplateRendererInterface;

class UsersHandlerFactory
{
    public function __invoke(ContainerInterface $container): UsersHandler
    {
        return new UsersHandler($container->get(TemplateRendererInterface::class));
    }
}
