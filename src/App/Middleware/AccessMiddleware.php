<?php

declare(strict_types=1);

namespace App\Middleware;

use App\DataModel;
use App\Model\Resource;
use App\Permissions;
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
    /** @var AuthenticationInterface|null */
    private $auth;

    /** @var array */
    private $configAuthorization;

    /** @var string */
    protected $redirect;

    /** @var TemplateRendererInterface */
    private $renderer;

    /** @var TableIdentifier */
    private $tableResource;

    /** @var TableIdentifier */
    private $tableRole;

    /** @var TableIdentifier */
    private $tableUser;

    /** @var TableIdentifier */
    private $tableRoleResource;

    /** @var TableIdentifier */
    private $tableUserRole;

    public function __construct(
        ?AuthenticationInterface $auth,
        string $redirect,
        array $configAuthorization,
        TableIdentifier $tableResource,
        TableIdentifier $tableRole,
        TableIdentifier $tableUser,
        TableIdentifier $tableRoleResource,
        TableIdentifier $tableUserRole,
        TemplateRendererInterface $renderer
    ) {
        $this->auth = $auth;
        $this->configAuthorization = $configAuthorization;
        $this->redirect = $redirect;
        $this->renderer = $renderer;
        $this->tableResource = $tableResource;
        $this->tableRole = $tableRole;
        $this->tableUser = $tableUser;
        $this->tableRoleResource = $tableRoleResource;
        $this->tableUserRole = $tableUserRole;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $adapter = $request->getAttribute(DbMiddleware::class);
        $basePath = $request->getAttribute(BaseUrlMiddleware::BASE_PATH);

        $acl = new Permissions(
            $this->configAuthorization,
            $adapter,
            $this->tableResource,
            $this->tableRole,
            $this->tableUser,
            $this->tableRoleResource,
            $this->tableUserRole
        );

        if (null !== $this->auth) {
            $user = $this->auth->authenticate($request);
            if (null !== $user) {
                $request = $request->withAttribute(AclInterface::class, $acl);
                $request = $request->withAttribute(UserInterface::class, $user);
            } else {
                // return $this->auth->unauthorizedResponse($request);

                return new RedirectResponse($basePath !== '/' ? $basePath : ''.$this->redirect);
            }
        }

        $resources = DataModel::getResources($adapter, $this->tableResource);
        $homepages = array_values(array_filter($resources, function (Resource $resource) use ($acl, $user): bool {
            return preg_match('/^home-.+$/', $resource->name) === 1
                && $acl->isAllowed($user->getIdentity(), $resource->name);
        }));

        $this->renderer->addDefaultParam($this->renderer::TEMPLATE_ALL, 'acl', $acl);
        $this->renderer->addDefaultParam($this->renderer::TEMPLATE_ALL, 'user', $user);
        $this->renderer->addDefaultParam($this->renderer::TEMPLATE_ALL, 'homepages', $homepages);

        return $handler->handle($request);
    }
}
