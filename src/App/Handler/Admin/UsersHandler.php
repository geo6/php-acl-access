<?php

declare(strict_types=1);

namespace App\Handler\Admin;

use App\DataModel;
use App\Middleware\DbMiddleware;
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
    private $tableRole;

    /** @var TableIdentifier */
    private $tableUser;

    /** @var TableIdentifier */
    private $tableUserRole;

    public function __construct(
        TemplateRendererInterface $renderer,
        TableIdentifier $tableRole,
        TableIdentifier $tableUser,
        TableIdentifier $tableUserRole
    ) {
        $this->renderer = $renderer;
        $this->tableRole = $tableRole;
        $this->tableUser = $tableUser;
        $this->tableUserRole = $tableUserRole;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // Check access
        $user = $request->getAttribute(UserInterface::class);
        $acl = $request->getAttribute(AclInterface::class);
        if ($acl->isAllowed($user->getIdentity(), 'admin.access') !== true) {
            return new HtmlResponse($this->renderer->render('error::403'), 403);
        }

        //
        $adapter = $request->getAttribute(DbMiddleware::class);

        return new HtmlResponse($this->renderer->render(
            'app::admin/users',
            [
                'users' => DataModel::getUsers($adapter, $this->tableUser, $this->tableRole, $this->tableUserRole),
                'roles' => DataModel::getRoles($adapter, $this->tableRole),
            ]
        ));
    }
}
