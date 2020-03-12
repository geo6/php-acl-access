<?php

declare(strict_types=1);

namespace App\Middleware;

use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerInterface;

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
