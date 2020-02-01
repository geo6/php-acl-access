<?php

declare(strict_types=1);

namespace App\Middleware;

use Psr\Container\ContainerInterface;
use Zend\Expressive\Template\TemplateRendererInterface;

class TemplateMiddlewareFactory
{
    public function __invoke(ContainerInterface $container): TemplateMiddleware
    {
        $config = $container->get('config');

        return new TemplateMiddleware(
            $container->get(TemplateRendererInterface::class),
            [
                'logo'         => $config['logo'] ?? null,
                'title'        => $config['title'] ?? null,
                'attributions' => $config['attributions'] ?? null,
            ]
        );
    }
}
