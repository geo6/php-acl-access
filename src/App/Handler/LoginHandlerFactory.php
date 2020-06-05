<?php

declare(strict_types=1);

namespace App\Handler;

use Exception;
use Mezzio\Authentication\Session\PhpSession;
use Mezzio\Router\RouterInterface;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerInterface;

/**
 * @see https://docs.zendframework.com/zend-expressive-authentication-session/v1/login-handler/
 */
class LoginHandlerFactory
{
    public function __invoke(ContainerInterface $container): LoginHandler
    {
        $config = $container->get('config');

        if (isset($config['reCAPTCHA'])) {
            if (!isset($config['reCAPTCHA']['key'])) {
                throw new Exception('Missing parameter "key" for reCAPTCHA.');
            }
            if (!isset($config['reCAPTCHA']['secret'])) {
                throw new Exception('Missing parameter "secret" for reCAPTCHA.');
            }
            if (isset($config['reCAPTCHA']['threshold']) && (!is_float($config['reCAPTCHA']['threshold']) || $config['reCAPTCHA']['threshold'] < 0.0 || $config['reCAPTCHA']['threshold'] > 1.0)) {
                throw new Exception('Invalid parameter "threshold" for reCAPTCHA. It should be defined as a number between 0.0 and 1.0.');
            }
        }

        return new LoginHandler(
            $container->get(TemplateRendererInterface::class),
            $container->get(RouterInterface::class),
            $container->get(PhpSession::class),
            [
                'authentication' => $config['authentication'] ?? [],
                'reCAPTCHA'      => $config['reCAPTCHA'] ?? null,
            ]
        );
    }
}
