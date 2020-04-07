<?php

declare(strict_types=1);

namespace App;

use App\Model\Resource;
use App\Model\Role;
use App\Model\User;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Sql\Expression;
use Laminas\Db\Sql\Sql;
use Laminas\Db\Sql\TableIdentifier;
use Laminas\Hydrator\ReflectionHydrator;

class DataModel
{
    public static function getResources(Adapter $adapter, TableIdentifier $table): array
    {
        $sql = new Sql($adapter);

        $select = $sql->select(['r' => $table]);
        $select->columns([
            'id',
            'name',
            'path',
        ]);
        $select->order('name');

        $result = $adapter->query($sql->buildSqlString($select), $adapter::QUERY_MODE_EXECUTE)->toArray();

        $resources = [];
        foreach ($result as $record) {
            $resources[] = (new ReflectionHydrator())->hydrate($record, new Resource());
        }

        return $resources;
    }

    public static function getRoles(Adapter $adapter, TableIdentifier $table): array
    {
        $sql = new Sql($adapter);

        $select = $sql->select(['r' => $table]);
        $select->columns([
            'id',
            'name',
            'priority',
        ]);
        $select->order(['priority DESC', 'name']);

        $result = $adapter->query($sql->buildSqlString($select), $adapter::QUERY_MODE_EXECUTE)->toArray();

        $roles = [];
        foreach ($result as $record) {
            $roles[] = (new ReflectionHydrator())->hydrate($record, new Role());
        }

        return $roles;
    }

    public static function getRolesResources(Adapter $adapter, TableIdentifier $table, TableIdentifier $tableResource, TableIdentifier $tableRole): array
    {
        $sql = new Sql($adapter);

        $select = $sql->select(['rr' => $table]);
        $select->columns([]);
        $select->join(['re' => $tableResource], 're.id = rr.id_resource', [
            'resource' => new Expression('row_to_json(re.*)'),
        ]);
        $select->join(['ro' => $tableRole], 'ro.id = rr.id_role', [
            'role' => new Expression('row_to_json(ro.*)'),
        ]);

        $result = $adapter->query($sql->buildSqlString($select), $adapter::QUERY_MODE_EXECUTE)->toArray();

        $rr = [];
        foreach ($result as $record) {
            $rr[] = [
                'resource' => (new ReflectionHydrator())->hydrate(json_decode($record['resource'], true), new Resource()),
                'role'     => (new ReflectionHydrator())->hydrate(json_decode($record['role'], true), new Role()),
            ];
        }

        return $rr;
    }

    public static function getUsers(Adapter $adapter, TableIdentifier $table, TableIdentifier $tableRole, TableIdentifier $tableUserRole): array
    {
        $roles = self::getRoles($adapter, $tableRole);

        $sql = new Sql($adapter);

        $select = $sql->select(['u' => $table]);
        $select->columns([
            'id',
            'login',
            'fullname',
            'email',
            'redirect',
            '_roles' => $sql->select(['ur' => $tableUserRole])->columns([new Expression('to_json(array_agg(id_role))')])->where('ur.id_user = u.id'),
        ]);
        $select->order('login');

        $result = $adapter->query($sql->buildSqlString($select), $adapter::QUERY_MODE_EXECUTE)->toArray();

        $users = [];
        foreach ($result as $record) {
            $user = (new ReflectionHydrator())->hydrate($record, new User());

            if (!is_null($record['_roles'])) {
                $ids = json_decode($record['_roles']);

                $userRoles = array_filter($roles, function (Role $role) use ($ids) {
                    return in_array($role->id, $ids);
                });

                foreach ($userRoles as $role) {
                    $user->addRole($role);
                }
            }

            $users[] = $user;
        }

        return $users;
    }
}
