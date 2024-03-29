<?php

declare(strict_types=1);

namespace App;

use Exception;
use Mezzio\Authentication\DefaultUser;
use Mezzio\Authentication\UserInterface;
use Mezzio\Authentication\UserRepository\PdoDatabase;
use PDO;

class UserRepository extends PdoDatabase
{
    /** @var array */
    private $config;

    /** @var PDO */
    private $pdo;

    public function __construct(array $config)
    {
        $this->config = $config;

        $this->pdo = new PDO(
            $config['dsn'],
            $config['username'] ?? null,
            $config['password'] ?? null
        );

        parent::__construct($this->pdo, $config, __NAMESPACE__.'\UserRepository::createUser');
    }

    public function search(string $column, string $value): ?UserInterface
    {
        $sql = sprintf(
            'SELECT %s FROM %s WHERE %s = :value',
            $this->config['field']['identity'],
            $this->config['table'],
            $column
        );
/** @var \PDOStatement|false */ $stmt = $this->pdo->prepare($sql);

        if (false === $stmt) {
            throw new Exception(
                'An error occurred when preparing to fetch user details from '.
                    'the repository; please verify your configuration'
            );
        }

        $stmt->bindParam(':value', $value);

        $stmt->execute();

        $result = $stmt->fetchObject();
        if ($result === false) {
            return null;
        }

        $identity = $result->{$this->config['field']['identity']};

        return self::createUser(
            $identity,
            $this->getUserRoles($identity),
            $this->getUserDetails($identity)
        );
    }

    public static function createUser(string $identity, array $roles = [], array $details = []): DefaultUser
    {
        return new DefaultUser($identity, $roles, $details);
    }
}
