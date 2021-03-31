<?php

declare(strict_types=1);

namespace App\Handler\Admin;

use App\DataModel;
use App\Middleware\DbMiddleware;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\ResultSet\ResultSet;
use Laminas\Db\Sql\Expression;
use Laminas\Db\Sql\Sql;
use Laminas\Db\Sql\TableIdentifier;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Permissions\Acl\AclInterface;
use Mezzio\Authentication\UserInterface;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class RolesHandler implements RequestHandlerInterface
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
        TableIdentifier $tableResource,
        TableIdentifier $tableRole,
        TableIdentifier $tableUser,
        TableIdentifier $tableUserRole
    ) {
        $this->renderer = $renderer;
        $this->tableResource = $tableResource;
        $this->tableRole = $tableRole;
        $this->tableUser = $tableUser;
        $this->tableUserRole = $tableUserRole;
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

        return new HtmlResponse($this->renderer->render(
            'app::admin/roles',
            [
                'roles'     => DataModel::getRoles($adapter, $this->tableRole),
                'resources' => DataModel::getResources($adapter, $this->tableResource),
                'users'     => $this->getUsersByRole($adapter),
            ]
        ));
    }

    private function getUsersByRole(Adapter $adapter): array
    {
        $sql = new Sql($adapter);

        $select = $sql->select(['ur' => $this->tableUserRole]);
        $select->columns(['id_role']);
        $select->join(
            ['u' => $this->tableUser],
            'ur.id_user = u.id',
            [
                '_users' => new Expression('to_json(array_agg(u.login ORDER BY login))'),

            ]
        );
        $select->group('id_role');
        $select->order('id_role');

/** @var ResultSet */ $result = $adapter->query($sql->buildSqlString($select), $adapter::QUERY_MODE_EXECUTE);

        $usersByRole = [];
        foreach ($result as $record) {
            $usersByRole[$record['id_role']] = json_decode($record['_users'], true);
        }

        $roles = DataModel::getRoles($adapter, $this->tableRole);
        foreach ($roles as $role) {
            if (!isset($usersByRole[$role->id])) {
                $usersByRole[$role->id] = [];
            }
        }

        return $usersByRole;
    }
}
