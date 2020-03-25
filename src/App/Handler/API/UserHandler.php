<?php

declare(strict_types=1);

namespace App\Handler\API;

use App\DataModel;
use App\Model\User;
use Geo6\Zend\Log\Log;
use Hackzilla\PasswordGenerator\Generator\ComputerPasswordGenerator;
use Hackzilla\PasswordGenerator\RandomGenerator\Php7RandomGenerator;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Sql\Sql;
use Laminas\Db\Sql\TableIdentifier;
use Laminas\Db\TableGateway\Feature\SequenceFeature;
use Laminas\Log\Logger;

class UserHandler extends DefaultHandler
{
    /** @var TableIdentifier */
    private $tableRole;

    /** @var TableIdentifier */
    private $tableUserRole;

    public function __construct(TableIdentifier $tableUser, TableIdentifier $tableRole, TableIdentifier $tableUserRole)
    {
        $this->init(
            $tableUser,
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

        Log::write(
            sprintf('data/log/%s.log', date('Ym')),
            'New user "{username}" created.',
            ['username' => $user->login],
            Logger::NOTICE
        );

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

    protected function delete(Adapter $adapter, $user): User
    {
        $user = parent::delete($adapter, $user);

        Log::write(
            sprintf('data/log/%s.log', date('Ym')),
            'User "{username}" deleted.',
            ['username' => $user->login],
            Logger::WARN
        );

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

    private static function updateRoles(Adapter $adapter, TableIdentifier $table, int $id, array $roles): void
    {
        $sql = new Sql($adapter);

        $delete = $sql->delete($table);
        $delete->where->equalTo('id_user', $id);

        $adapter->query($sql->buildSqlString($delete), $adapter::QUERY_MODE_EXECUTE);

        foreach ($roles as $roleId) {
            $insert = $sql->insert($table);
            $insert->columns(['id_user', 'id_role']);
            $insert->values([$id, $roleId]);

            $adapter->query($sql->buildSqlString($insert), $adapter::QUERY_MODE_EXECUTE);
        }
    }
}
