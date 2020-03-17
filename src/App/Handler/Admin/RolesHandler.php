<?php

declare(strict_types=1);

namespace App\Handler\Admin;

use App\DataModel;
use App\Middleware\DbMiddleware;
use ArrayObject;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Sql\Expression;
use Laminas\Db\Sql\Sql;
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

    /** @var ArrayObject */
    private $tables;

    public function __construct(TemplateRendererInterface $renderer, ArrayObject $tables)
    {
        $this->renderer = $renderer;
        $this->tables = $tables;
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
            'app::admin/roles',
            [
                'roles'     => DataModel::getRoles($adapter, $this->tables->role),
                'resources' => DataModel::getResources($adapter, $this->tables->resource),
                'users'     => self::getUsersByRole($adapter, $this->tables->user_role, $this->tables->user),
            ]
        ));
    }

    private static function getUsersByRole(Adapter $adapter, string $tableUserRole, string $tableUser): array
    {
        $sql = new Sql($adapter);

        $select = $sql->select(['ur' => $tableUserRole]);
        $select->columns([
            'id_role',
        ]);
        $select->join(
            ['u' => $tableUser],
            'ur.id_user = u.id',
            [
                '_users' => new Expression('to_json(array_agg(u.login ORDER BY login))'),

            ]
        );
        $select->group('id_role');
        $select->order('id_role');

        $result = $adapter->query($sql->buildSqlString($select), $adapter::QUERY_MODE_EXECUTE)->toArray();

        $roles = [];
        foreach ($result as $record) {
            $roles[$record['id_role']] = json_decode($record['_users'], true);
        }

        return $roles;
    }
}
