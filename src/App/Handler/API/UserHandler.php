<?php

declare(strict_types=1);

namespace App\Handler\API;

use App\DataModel;
use App\Model\User;
use Hackzilla\PasswordGenerator\Generator\ComputerPasswordGenerator;
use Hackzilla\PasswordGenerator\RandomGenerator\Php7RandomGenerator;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\TableGateway\Feature\SequenceFeature;

class UserHandler extends DefaultHandler
{
    /** @var string */
    private $tableRole;

    /** @var string */
    private $tableUserRole;

    public function __construct(string $table, string $tableRole, string $tableUserRole)
    {
        $this->init(
            $table,
            new SequenceFeature('id', 'users_id_seq'),
            User::class
        );

        $this->tableRole = $tableRole;
        $this->tableUserRole = $tableUserRole;
    }

    protected static function toArray($object): array
    {
        $userArray = $object->jsonSerialize();

        unset($userArray['roles']);

        return $userArray;
    }

    protected function getObjects(Adapter $adapter): array
    {
        return DataModel::getUsers($adapter, $this->table, $this->tableRole, $this->tableUserRole);
    }

    protected function insert(Adapter $adapter, array $data): User
    {
        $data['user']['password'] = password_hash(self::generatePassword(), PASSWORD_DEFAULT);

        $user = parent::insert($adapter, $data['user']);

        if (isset($data['roles'])) {
            self::updateRoles($adapter, $this->tableUserRole, $user->id, $data['roles']);

            // To-Do: Update $user with roles!
        }

        return $user;
    }

    protected function update(Adapter $adapter, $user, array $data): User
    {
        $user = parent::update($adapter, $user, $data['user']);

        if (isset($data['roles'])) {
            self::updateRoles($adapter, $this->tableUserRole, $user->id, $data['roles']);

            // To-Do: Update $user with roles!
        }

        return $user;
    }

    private static function generatePassword(): string
    {
        $generator = new ComputerPasswordGenerator(new Php7RandomGenerator());

        $generator
            ->setUppercase(true)
            ->setLowercase(true)
            ->setNumbers(true)
            ->setSymbols(false)
            ->setAvoidSimilar(true)
            ->setLength(8);

        return $generator->generatePassword();
    }

    private static function updateRoles(Adapter $adapter, string $table, int $id, array $roles): void
    {
        $adapter->query(sprintf('DELETE FROM %s WHERE id_user = ?', $table), [$id]);

        foreach ($roles as $roleId) {
            $adapter->query(
                sprintf('INSERT INTO %s (id_user, id_role) VALUES (?, ?)', $table),
                [$id, $roleId]
            );
        }
    }
}
