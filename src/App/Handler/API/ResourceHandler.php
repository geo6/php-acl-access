<?php

declare(strict_types=1);

namespace App\Handler\API;

use App\DataModel;
use App\Model\Resource;
use Zend\Db\Adapter\Adapter;
use Zend\Db\TableGateway\Feature\SequenceFeature;

class ResourceHandler extends DefaultHandler
{
    public function __construct()
    {
        $this->init(
            DataModel::TABLE_RESOURCE,
            new SequenceFeature('id', 'resources_id_seq'),
            Resource::class
        );
    }

    protected function getObjects(Adapter $adapter): array
    {
        return DataModel::getResources($adapter);
    }
}
