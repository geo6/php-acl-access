<?php

declare(strict_types=1);

namespace App\Handler\API;

use App\DataModel;
use App\Model\Role;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\TableGateway\Feature\SequenceFeature;

class RoleHandler extends DefaultHandler
{
    public function __construct(string $table)
    {
        $this->init(
            $table,
            new SequenceFeature('id', 'roles_id_seq'),
            Role::class
        );
    }

    protected function getObjects(Adapter $adapter): array
    {
        return DataModel::getRoles($adapter, $this->table);
    }
}
