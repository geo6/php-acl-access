<?php

declare(strict_types=1);

namespace App\Handler\Admin;

use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerInterface;

class UsersHandlerFactory
{
    public function __invoke(ContainerInterface $container): UsersHandler
    {
        return new UsersHandler($container->get(TemplateRendererInterface::class));
    }
}
