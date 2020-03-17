<?php

declare(strict_types=1);

namespace App\Handler\Admin;

use ArrayObject;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerInterface;

class RolesHandlerFactory
{
    public function __invoke(ContainerInterface $container): RolesHandler
    {
        $config = $container->get('config');

        return new RolesHandler(
            $container->get(TemplateRendererInterface::class),
            new ArrayObject($config['tables'], ArrayObject::ARRAY_AS_PROPS)
        );
    }
}
