<?php

declare(strict_types=1);

namespace App;

use Laminas\Permissions\Acl\Acl;
use Laminas\Permissions\Acl\Exception\ExceptionInterface as AclExceptionInterface;
use Mezzio\Authentication\Exception as AuthenticationException;
use PDO;
use PDOException;

class Permissions extends Acl
{
    private $pdo;

    public function __construct(?array $pdo, array $configAuth, ?array $configTables = null)
    {
        if (!is_null($pdo)) {
            $this->pdo = self::connect($pdo);

            $this->injectUsersRoles($pdo);
            $this->injectResources($configTables);
            $this->injectPermissions($configTables);
        }

        $this->injectConfigRoles($configAuth['roles'] ?? []);
        $this->injectConfigResources($configAuth['resources'] ?? []);
        $this->injectConfigPermissions($configAuth['allow'] ?? [], 'allow');
        $this->injectConfigPermissions($configAuth['deny'] ?? [], 'deny');
    }

    /**
     * Create PDO connection.
     */
    private static function connect(array $config): PDO
    {
        if (!isset($config['dsn'])) {
            throw new AuthenticationException\InvalidConfigException(
                'The PDO DSN value is missing in the configuration'
            );
        }
        if (!isset($config['table'])) {
            throw new AuthenticationException\InvalidConfigException(
                'The PDO table name is missing in the configuration'
            );
        }
        if (!isset($config['field']['identity'])) {
            throw new AuthenticationException\InvalidConfigException(
                'The PDO identity field is missing in the configuration'
            );
        }
        if (!isset($config['field']['password'])) {
            throw new AuthenticationException\InvalidConfigException(
                'The PDO password field is missing in the configuration'
            );
        }

        return new PDO(
            $config['dsn'],
            $config['username'] ?? null,
            $config['password'] ?? null
        );
    }

    /**
     * Add User and Role from database.
     */
    private function injectUsersRoles(array $config): void
    {
        $sqlUser = sprintf(
            'SELECT %s FROM %s',
            $config['field']['identity'],
            $config['table']
        );
        $stmtUser = $this->pdo->prepare($sqlUser);

        if (false === $stmtUser) {
            throw new AuthenticationException\RuntimeException(
                'An error occurred when preparing to fetch user details from '.
                    'the repository; please verify your configuration'
            );
        }

        $stmtUser->execute();

        foreach ($stmtUser->fetchAll(PDO::FETCH_NUM) as $user) {
            if (!isset($config['sql_get_roles'])) {
                $this->addRole($user);
            } else {
                if (false === strpos($config['sql_get_roles'], ':identity')) {
                    throw new AuthenticationException\InvalidConfigException(
                        'The sql_get_roles configuration setting must include an :identity parameter'
                    );
                }

                try {
                    $stmtRoles = $this->pdo->prepare($config['sql_get_roles']);
                } catch (PDOException $e) {
                    throw new AuthenticationException\RuntimeException(sprintf(
                        'Error preparing retrieval of user roles: %s',
                        $e->getMessage()
                    ));
                }

                if (false === $stmtRoles) {
                    throw new AuthenticationException\RuntimeException(sprintf(
                        'Error preparing retrieval of user roles: unknown error'
                    ));
                }

                $stmtRoles->bindParam(':identity', $user[0]);

                if (!$stmtRoles->execute()) {
                    $this->addRole($user[0], []);
                }

                $roles = [];
                foreach ($stmtRoles->fetchAll(PDO::FETCH_NUM) as $role) {
                    $roles[] = $role[0];
                    if (!$this->hasRole($role[0])) {
                        $this->addRole($role[0]);
                    }
                }

                $this->addRole($user[0], $roles);
            }
        }
    }

    /**
     * Add Resources from database.
     */
    private function injectResources(array $tables): void
    {
        $sqlResource = sprintf(
            'SELECT "name" FROM %s',
            $tables['resource']
        );
        $stmtResource = $this->pdo->prepare($sqlResource);

        if (false === $stmtResource) {
            throw new AuthenticationException\RuntimeException(
                'An error occurred when preparing to fetch resources details from '.
                    'the repository; please verify your configuration'
            );
        }

        $stmtResource->execute();

        foreach ($stmtResource->fetchAll(PDO::FETCH_NUM) as $resource) {
            $this->addResource($resource[0]);
        }
    }

    /**
     * Add Allow/Deny permission from database.
     */
    private function injectPermissions(array $tables): void
    {
        $sql = sprintf(
            'SELECT ro."name", re."name" FROM %s rr JOIN %s ro ON rr."id_role" = ro."id" JOIN %s re ON rr."id_resource" = re."id"',
            $tables['role_resource'],
            $tables['role'],
            $tables['resource']
        );
        $stmt = $this->pdo->prepare($sql);

        if (false === $stmt) {
            throw new AuthenticationException\RuntimeException(
                'An error occurred when preparing to fetch roles/resources details from '.
                    'the repository; please verify your configuration'
            );
        }

        $stmt->execute();

        foreach ($stmt->fetchAll(PDO::FETCH_NUM) as $rr) {
            $this->allow($rr[0], $rr[1]);
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
