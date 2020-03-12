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

    public function search(string $email): ?UserInterface
    {
        $sql = sprintf(
            'SELECT %s FROM %s WHERE %s = :email',
            $this->config['field']['identity'],
            $this->config['table'],
            $this->config['field']['email']
        );
        $stmt = $this->pdo->prepare($sql);

        if (false === $stmt) {
            throw new Exception(
                'An error occurred when preparing to fetch user details from '.
                    'the repository; please verify your configuration'
            );
        }

        $stmt->bindParam(':email', $email);

        $stmt->execute();

        $result = $stmt->fetchObject();
        if (!$result) {
            return null;
        }

        $identity = $result->{$this->config['field']['identity']};

        return self::createUser(
            $identity,
            $this->getUserRoles($identity),
            $this->getUserDetails($identity)
        );
    }

    public static function createUser(string $identity, array $roles = [], array $details = [])
    {
        return new DefaultUser($identity, $roles, $details);
    }
}
