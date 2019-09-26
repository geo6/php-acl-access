<?php

declare(strict_types=1);

namespace App\Handler;

use Psr\Container\ContainerInterface;
use Zend\Expressive\Router\RouterInterface;
use Zend\Expressive\Template\TemplateRendererInterface;

class PasswordHandlerFactory
{
    public function __invoke(ContainerInterface $container): PasswordHandler
    {
        $config = $container->get('config');

        return new PasswordHandler(
            $container->get(TemplateRendererInterface::class),
            $container->get(RouterInterface::class),
            [
                'mail' => $config['mail'] ?? [],
                'authentication' => $config['authentication'] ?? [],
            ]
        );
    }
}
