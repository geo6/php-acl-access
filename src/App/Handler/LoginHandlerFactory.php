<?php

declare(strict_types=1);

namespace App\Handler;

use Psr\Container\ContainerInterface;
use Zend\Expressive\Authentication\Session\PhpSession;
use Zend\Expressive\Router\RouterInterface;
use Zend\Expressive\Template\TemplateRendererInterface;

/**
 * @see https://docs.zendframework.com/zend-expressive-authentication-session/v1/login-handler/
 */
class LoginHandlerFactory
{
    public function __invoke(ContainerInterface $container) : LoginHandler
    {
        return new LoginHandler(
            $container->get(TemplateRendererInterface::class),
            $container->get(RouterInterface::class),
            $container->get(PhpSession::class)
        );
    }
}
