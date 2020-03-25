<?php

declare(strict_types=1);

namespace App\Middleware;

use App\DataModel;
use App\Model\Resource;
use Blast\BaseUrl\BaseUrlMiddleware;
use Laminas\Db\Sql\TableIdentifier;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Permissions\Acl\AclInterface;
use Mezzio\Authentication\AuthenticationInterface;
use Mezzio\Authentication\UserInterface;
use Mezzio\Template\TemplateRendererInterface;
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

    /** @var TemplateRendererInterface */
    private $renderer;

    /** @var TableIdentifier */
    private $tableResource;

    public function __construct(
        ?AuthenticationInterface $auth,
        string $redirect,
        AclInterface $acl,
        TableIdentifier $tableResource,
        TemplateRendererInterface $renderer
    ) {
        $this->acl = $acl;
        $this->auth = $auth;
        $this->redirect = $redirect;
        $this->renderer = $renderer;
        $this->tableResource = $tableResource;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $adapter = $request->getAttribute(DbMiddleware::class);
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

        $resources = DataModel::getResources($adapter, $this->tableResource);
        $homepages = array_values(array_filter($resources, function (Resource $resource) use ($user) {
            return preg_match('/^home-.+$/', $resource->name) === 1
                && $this->acl->isAllowed($user->getIdentity(), $resource->name);
        }));

        $this->renderer->addDefaultParam($this->renderer::TEMPLATE_ALL, 'acl', $this->acl);
        $this->renderer->addDefaultParam($this->renderer::TEMPLATE_ALL, 'user', $user);
        $this->renderer->addDefaultParam($this->renderer::TEMPLATE_ALL, 'homepages', $homepages);

        return $handler->handle($request);
    }
}
