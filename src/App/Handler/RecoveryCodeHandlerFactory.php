<?php

declare(strict_types=1);

namespace App\Handler;

use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerInterface;

class RecoveryCodeHandlerFactory
{
    public function __invoke(ContainerInterface $container): RecoveryCodeHandler
    {
        $config = $container->get('config');

        return new RecoveryCodeHandler(
            $container->get(TemplateRendererInterface::class),
            [
                'mail'           => $config['mail'] ?? [],
                'authentication' => $config['authentication'] ?? [],
            ]
        );
    }
}
