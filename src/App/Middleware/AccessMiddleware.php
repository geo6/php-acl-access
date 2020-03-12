<?php

declare(strict_types=1);

namespace App\Middleware;

use Blast\BaseUrl\BaseUrlMiddleware;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Permissions\Acl\AclInterface;
use Mezzio\Authentication\AuthenticationInterface;
use Mezzio\Authentication\UserInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * @see \Mezzio\Authentication\AuthenticationMiddleware
 */
class AccessMiddleware implements MiddlewareInterface
{
    /** @var AclInterface */
    protected $acl;

    /** @var AuthenticationInterface */
    protected $auth;

    /** @var string */
    protected $redirect;

    public function __construct(?AuthenticationInterface $auth, string $redirect, AclInterface $acl)
    {
        $this->acl = $acl;
        $this->auth = $auth;
        $this->redirect = $redirect;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $basePath = $request->getAttribute(BaseUrlMiddleware::BASE_PATH);

        if (null !== $this->auth) {
            $user = $this->auth->authenticate($request);
            if (null !== $user) {
                $request = $request->withAttribute(UserInterface::class, $user);
                $request = $request->withAttribute(AclInterface::class, $this->acl);
            } else {
                // return $this->auth->unauthorizedResponse($request);

                return new RedirectResponse($basePath !== '/' ? $basePath : ''.$this->redirect);
            }
        }

        return $handler->handle($request);
    }
}
