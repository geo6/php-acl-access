<?php

declare(strict_types=1);

namespace App\Handler;

use Psr\Container\ContainerInterface;
use Zend\Expressive\Template\TemplateRendererInterface;

class RecoveryCodeHandlerFactory
{
    public function __invoke(ContainerInterface $container): RecoveryCodeHandler
    {
        $config = $container->get('config');

        return new RecoveryCodeHandler(
            $container->get(TemplateRendererInterface::class),
            [
                'mail' => $config['mail'] ?? [],
                'authentication' => $config['authentication'] ?? [],
            ]
        );
    }
}
