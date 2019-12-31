<?php

declare(strict_types=1);

namespace App;

use App\Model\Resource;
use App\Model\Role;
use App\Model\User;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Predicate\Expression;
use Zend\Db\Sql\Sql;
use Zend\Hydrator\ReflectionHydrator;

class DataModel
{
    const TABLE_RESOURCE = 'resources';
    const TABLE_ROLE = 'roles';
    const TABLE_USER = 'users';
    const TABLE_USER_ROLE = 'user_role';

    public static function getResources(Adapter $adapter): array
    {
        $sql = new Sql($adapter);

        $select = $sql->select(['r' => self::TABLE_RESOURCE]);
        $select->columns([
            'id',
            'name',
            'path',
        ]);
        $select->order('name');

        $result = $adapter->query($sql->buildSqlString($select), $adapter::QUERY_MODE_EXECUTE)->toArray();

        $resources = [];
        foreach ($result as $record) {
            $resources[] = (new ReflectionHydrator())->hydrate($record, new Resource);
        }

        return $resources;
    }

    public static function getRoles(Adapter $adapter): array
    {
        $sql = new Sql($adapter);

        $select = $sql->select(['r' => self::TABLE_ROLE]);
        $select->columns([
            'id',
            'name',
            'priority',
        ]);
        $select->order(['priority DESC', 'name']);

        $result = $adapter->query($sql->buildSqlString($select), $adapter::QUERY_MODE_EXECUTE)->toArray();

        $roles = [];
        foreach ($result as $record) {
            $roles[] = (new ReflectionHydrator())->hydrate($record, new Role);
        }

        return $roles;
    }

    public static function getUsers(Adapter $adapter): array
    {
        $roles = self::getRoles($adapter);

        $sql = new Sql($adapter);

        $select = $sql->select(['u' => self::TABLE_USER]);
        $select->columns([
            'id',
            'login',
            'fullname',
            'email',
            '_roles' => $sql->select(['ur' => self::TABLE_USER_ROLE])->columns([new Expression('to_json(array_agg(id_role))')])->where('ur.id_user = u.id')
        ]);
        $select->order('login');

        $result = $adapter->query($sql->buildSqlString($select), $adapter::QUERY_MODE_EXECUTE)->toArray();

        $users = [];
        foreach ($result as $record) {
            $user = (new ReflectionHydrator())->hydrate($record, new User);

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
