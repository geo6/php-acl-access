<?php

declare(strict_types=1);

namespace App\Handler\Admin;

use App\DataModel;
use App\Middleware\DbMiddleware;
use App\Model\Resource;
use App\Model\User;
use Laminas\Db\Sql\TableIdentifier;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Permissions\Acl\AclInterface;
use Mezzio\Authentication\UserInterface;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class UsersHandler implements RequestHandlerInterface
{
    /** @var TemplateRendererInterface */
    private $renderer;

    /** @var TableIdentifier */
    private $tableResource;

    /** @var TableIdentifier */
    private $tableRole;

    /** @var TableIdentifier */
    private $tableUser;

    /** @var TableIdentifier */
    private $tableUserRole;

    public function __construct(
        TemplateRendererInterface $renderer,
        TableIdentifier $tableRole,
        TableIdentifier $tableUser,
        TableIdentifier $tableUserRole,
        TableIdentifier $tableResource
    ) {
        $this->renderer = $renderer;
        $this->tableRole = $tableRole;
        $this->tableUser = $tableUser;
        $this->tableUserRole = $tableUserRole;
        $this->tableResource = $tableResource;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // Check access
        $user = $request->getAttribute(UserInterface::class);
        $acl = $request->getAttribute(AclInterface::class);
        if ($acl->isAllowed($user->getIdentity(), 'admin.access', 'read') !== true) {
            return new HtmlResponse($this->renderer->render('error::403'), 403);
        }

        //
        $adapter = $request->getAttribute(DbMiddleware::class);

        $query = $request->getQueryParams();

        $resources = DataModel::getResources($adapter, $this->tableResource);
        $homepages = array_filter($resources, function ($resource): bool {
            return preg_match('/^home-.+$/', $resource->name) === 1;
        });
        $applications = array_filter($resources, function ($resource): bool {
            return preg_match('/^home-.+$/', $resource->name) !== 1;
        });

        $users = DataModel::getUsers($adapter, $this->tableUser, $this->tableRole, $this->tableUserRole);
        if (isset($query['role']) && strlen(trim($query['role'])) > 0) {
            $users = array_filter($users, function (User $user) use ($query): bool {
                return in_array($query['role'], $user->getRoles(), true) === true;
            });
        }
        $users = array_map(
            function (User $user) use ($resources): User {
                if (!is_null($user->redirect)) {
                    $user->redirect = current(array_filter($resources, function (Resource $resource) use ($user): bool {
                        return $user->redirect === $resource->id;
                    }));
                }

                return $user;
            },
            $users
        );

        return new HtmlResponse($this->renderer->render(
            'app::admin/users',
            [
                'users'     => $users,
                'roles'     => DataModel::getRoles($adapter, $this->tableRole),
                'resources' => [
                    'homepages'    => $homepages,
                    'applications' => $applications,
                ],
                'filter'    => [
                    'role' => $query['role'] ?? null,
                ],
            ]
        ));
    }
}
