<?php

declare(strict_types=1);

namespace App\Handler\API;

use App\DataModel;
use App\Model\Role;
use Zend\Db\Adapter\Adapter;
use Zend\Db\TableGateway\Feature\SequenceFeature;

class RoleHandler extends DefaultHandler
{
    public function __construct()
    {
        $this->init(
            DataModel::TABLE_ROLE,
            new SequenceFeature('id', 'roles_id_seq'),
            Role::class
        );
    }

    protected function getObjects(Adapter $adapter): array
    {
        return DataModel::getRoles($adapter);
    }
}
