<?php

declare(strict_types=1);

namespace App;

use Laminas\Db\Adapter\Adapter;
use Laminas\Db\ResultSet\ResultSet;
use Laminas\Db\Sql\Expression;
use Laminas\Db\Sql\Sql;
use Laminas\Db\Sql\TableIdentifier;
use Laminas\Permissions\Acl\Acl;
use Laminas\Permissions\Acl\Exception\ExceptionInterface as AclExceptionInterface;
use Mezzio\Authentication\Exception as AuthenticationException;

class Permissions extends Acl
{
    /** @var Adapter */
    private $adapter;

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
        array $configAuthorization,
        Adapter $adapter,
        TableIdentifier $tableResource,
        TableIdentifier $tableRole,
        TableIdentifier $tableUser,
        TableIdentifier $tableRoleResource,
        TableIdentifier $tableUserRole
    ) {
        $this->adapter = $adapter;

        $this->tableResource = $tableResource;
        $this->tableRole = $tableRole;
        $this->tableUser = $tableUser;
        $this->tableRoleResource = $tableRoleResource;
        $this->tableUserRole = $tableUserRole;

        $this->injectRoles();
        $this->injectUsersRoles();
        $this->injectResources();
        $this->injectPermissions();

        $this->injectConfigRoles($configAuthorization['roles'] ?? []);
        $this->injectConfigResources($configAuthorization['resources'] ?? []);
        $this->injectConfigPermissions($configAuthorization['allow'] ?? [], 'allow');
        $this->injectConfigPermissions($configAuthorization['deny'] ?? [], 'deny');
    }

    /**
     * Add Roles from database.
     */
    private function injectRoles(): void
    {
        $sql = new Sql($this->adapter);

        $select = $sql->select(['r' => $this->tableRole]);
        $select->columns(['name']);

/** @var ResultSet */ $result = $this->adapter->query($sql->buildSqlString($select), $this->adapter::QUERY_MODE_EXECUTE);

        foreach ($result as $r) {
            $this->addRole($r->name);
        }
    }

    /**
     * Add User and Role from database.
     */
    private function injectUsersRoles(): void
    {
        $sql = new Sql($this->adapter);

        // Add user(s) with role(s)
        $select = $sql->select(['ur' => $this->tableUserRole]);
        $select->columns([]);
        $select->join(['u' => $this->tableUser], 'u.id = ur.id_user', ['login']);
        $select->join(['r' => $this->tableRole], 'r.id = ur.id_role', ['roles' => new Expression('to_json(array_agg(name))')]);
        $select->group('u.login');

/** @var ResultSet */ $result = $this->adapter->query($sql->buildSqlString($select), $this->adapter::QUERY_MODE_EXECUTE);

        foreach ($result as $r) {
            $roles = json_decode($r->roles);

            $this->addRole($r->login, $roles);
        }

        // Add user(s) without role
        $select = $sql->select(['u' => $this->tableUser]);
        $select->columns(['login']);
        $select->where->notIn('id', $sql->select(['ur' => $this->tableUserRole])->columns(['id_user']));

/** @var ResultSet */ $result = $this->adapter->query($sql->buildSqlString($select), $this->adapter::QUERY_MODE_EXECUTE);

        foreach ($result as $r) {
            if (!$this->hasRole($r->login)) {
                $this->addRole($r->login);
            }
        }
    }

    /**
     * Add Resources from database.
     */
    private function injectResources(): void
    {
        $sql = new Sql($this->adapter);

        $select = $sql->select(['r' => $this->tableResource]);
        $select->columns(['name']);

/** @var ResultSet */ $result = $this->adapter->query($sql->buildSqlString($select), $this->adapter::QUERY_MODE_EXECUTE);

        foreach ($result as $r) {
            $this->addResource($r->name);
        }
    }

    /**
     * Add Allow/Deny permission from database.
     */
    private function injectPermissions(): void
    {
        $sql = new Sql($this->adapter);

        $select = $sql->select(['rr' => $this->tableRoleResource]);
        $select->columns([]);
        $select->join(['re' => $this->tableResource], 're.id = rr.id_resource', ['resource' => 'name']);
        $select->join(['ro' => $this->tableRole], 'ro.id = rr.id_role', ['role' => 'name']);

/** @var ResultSet */ $result = $this->adapter->query($sql->buildSqlString($select), $this->adapter::QUERY_MODE_EXECUTE);

        foreach ($result as $r) {
            $this->allow($r->role, $r->resource);
        }
    }

    /**
     * Add Role from config `authorization`.
     */
    private function injectConfigRoles(array $roles): void
    {
        foreach ($roles as $role => $parents) {
            foreach ($parents as $parent) {
                if (!$this->hasRole($parent)) {
                    try {
                        $this->addRole($parent);
                    } catch (AclExceptionInterface $e) {
                        throw new AuthenticationException\InvalidConfigException($e->getMessage(), $e->getCode(), $e);
                    }
                }
            }

            try {
                $this->addRole($role, $parents);
            } catch (AclExceptionInterface $e) {
                throw new AuthenticationException\InvalidConfigException($e->getMessage(), $e->getCode(), $e);
            }
        }
    }

    /**
     * Add Resource from config `authorization`.
     */
    private function injectConfigResources(array $resources): void
    {
        foreach ($resources as $resource) {
            try {
                $this->addResource($resource);
            } catch (AclExceptionInterface $e) {
                throw new AuthenticationException\InvalidConfigException($e->getMessage(), $e->getCode(), $e);
            }
        }
    }

    /**
     * Add Allow/Deny permission from config `authorization`.
     */
    private function injectConfigPermissions(array $permissions, string $type): void
    {
        if (!in_array($type, ['allow', 'deny'], true)) {
            throw new AuthenticationException\InvalidConfigException(sprintf(
                'Invalid permission type "%s" provided in configuration; must be one of "allow" or "deny"',
                $type
            ));
        }

        foreach ($permissions as $role => $resources) {
            try {
                if (is_array($resources)) {
                    foreach ($resources as $key => $value) {
                        if (is_string($value)) {
                            $this->$type($role, $value);
                        } elseif (is_array($value)) {
                            $this->$type($role, $key, $value);
                        } else {
                            throw new AuthenticationException\InvalidConfigException(sprintf(
                                'Permissions should be a string or an array ; %s provided.',
                                gettype($value)
                            ));
                        }
                    }
                } else {
                    $this->$type($role, $resources);
                }
            } catch (AclExceptionInterface $e) {
                throw new AuthenticationException\InvalidConfigException($e->getMessage(), $e->getCode(), $e);
            }
        }
    }
}
