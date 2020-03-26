<?php

declare(strict_types=1);

namespace App\Handler\API;

use App\DataModel;
use App\Model\Role;
use Geo6\Laminas\Log\Log;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Sql\TableIdentifier;
use Laminas\Db\TableGateway\Feature\SequenceFeature;
use Laminas\Log\Logger;

class RoleHandler extends DefaultHandler
{
    public function __construct(TableIdentifier $table)
    {
        $this->init(
            $table,
            new SequenceFeature('id', 'roles_id_seq'),
            Role::class
        );
    }

    protected function insert(Adapter $adapter, array $data): Role
    {
        $role = parent::insert($adapter, $data);

        Log::write(
            sprintf('data/log/%s-admin.log', date('Ym')),
            'New role "{role}" created.',
            ['role' => $role->name],
            Logger::NOTICE,
            $this->request
        );

        return $role;
    }

    protected function delete(Adapter $adapter, $role): Role
    {
        $role = parent::delete($adapter, $role);

        Log::write(
            sprintf('data/log/%s-admin.log', date('Ym')),
            'Role "{role}" deleted.',
            ['role' => $role->name],
            Logger::WARN,
            $this->request
        );

        return $role;
    }

    protected function getObjects(Adapter $adapter): array
    {
        return DataModel::getRoles($adapter, $this->table);
    }
}
