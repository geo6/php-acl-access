<?php

declare(strict_types=1);

namespace App\Handler\API;

use App\DataModel;
use App\Handler\Exception\FormException;
use App\Mail;
use App\Model\User;
use Geo6\Laminas\Log\Log;
use Hackzilla\PasswordGenerator\Generator\ComputerPasswordGenerator;
use Hackzilla\PasswordGenerator\RandomGenerator\Php7RandomGenerator;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Sql\Sql;
use Laminas\Db\Sql\TableIdentifier;
use Laminas\Db\TableGateway\Feature\SequenceFeature;
use Laminas\Log\Logger;
use Mezzio\Template\TemplateRendererInterface;

class UserHandler extends DefaultHandler
{
    /** @var array */
    private $configMail;

    /** @var TemplateRendererInterface */
    private $renderer;

    /** @var TableIdentifier */
    private $tableRole;

    /** @var TableIdentifier */
    private $tableUserRole;

    public function __construct(
        TableIdentifier $tableUser,
        TableIdentifier $tableRole,
        TableIdentifier $tableUserRole,
        TemplateRendererInterface $renderer,
        array $configMail
    ) {
        $this->init(
            $tableUser,
            new SequenceFeature('id', 'users_id_seq'),
            User::class
        );

        $this->tableRole = $tableRole;
        $this->tableUserRole = $tableUserRole;

        $this->configMail = $configMail;
        $this->renderer = $renderer;
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
        $users = DataModel::getUsers($adapter, $this->table, $this->tableRole, $this->tableUserRole);

        $login = $data['user']['login'];
        $checkLogin = array_filter($users, function (User $user) use ($login) {
            return $user->login === $login;
        });
        if (count($checkLogin) > 0) {
            throw new FormException('login', 'Login must be unique.');
        }

        $email = $data['user']['email'];
        $checkEmail = array_filter($users, function (User $user) use ($email) {
            return $user->email === $email;
        });
        if (count($checkEmail) > 0) {
            throw new FormException('email', 'Email address must be unique.');
        }

        $password = self::generatePassword();

        $data['user']['password'] = password_hash($password, PASSWORD_DEFAULT);

        $user = parent::insert($adapter, $data['user']);

        if (isset($data['roles'])) {
            self::updateRoles($adapter, $this->tableUserRole, $user->id, $data['roles']);

            // To-Do: Update $user with roles!
        }

        Mail::send(
            $this->configMail,
            $this->renderer,
            $user->email,
            'Account created',
            '@mail/account/create.html.twig',
            [
                'fullname' => $user->fullname,
                'login'    => $user->login,
                'password' => $password,
            ]
        );

        Log::write(
            sprintf('data/log/%s-admin.log', date('Ym')),
            'New user "{username}" created.',
            ['username' => $user->login],
            Logger::NOTICE,
            $this->request
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

        if (isset($data['user']['password'])) {
            $password = self::generatePassword();

            self::updatePassword($adapter, $this->table, $user->id, $password);

            Mail::send(
                $this->configMail,
                $this->renderer,
                $user->email,
                'Password reset',
                '@mail/password/reset.html.twig',
                [
                    'fullname' => $user->fullname,
                    'password' => $password,
                ]
            );

            Log::write(
                sprintf('data/log/%s-admin.log', date('Ym')),
                'Password for user "{username}" has been reset by an administrator.',
                ['username' => $user->login],
                Logger::NOTICE,
                $this->request
            );
        }

        return $user;
    }

    protected function delete(Adapter $adapter, $user): User
    {
        $user = parent::delete($adapter, $user);

        Mail::send(
            $this->configMail,
            $this->renderer,
            $user->email,
            'Account deleted',
            '@mail/account/delete.html.twig',
            [
                'fullname' => $user->fullname,
            ]
        );

        Log::write(
            sprintf('data/log/%s-admin.log', date('Ym')),
            'User "{username}" deleted.',
            ['username' => $user->login],
            Logger::WARN,
            $this->request
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

    private static function updatePassword(Adapter $adapter, TableIdentifier $table, int $id, string $password): void
    {
        $sql = new Sql($adapter);

        $update = $sql->update($table);
        $update->set(['password' => password_hash($password, PASSWORD_DEFAULT)]);
        $update->where->equalTo('id', $id);

        $adapter->query($sql->buildSqlString($update), $adapter::QUERY_MODE_EXECUTE);
    }
}
