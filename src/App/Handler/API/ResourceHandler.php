<?php

declare(strict_types=1);

namespace App\Handler\API;

use App\DataModel;
use App\Model\Resource;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\TableGateway\Feature\SequenceFeature;

class ResourceHandler extends DefaultHandler
{
    public function __construct(string $table)
    {
        $this->init(
            $table,
            new SequenceFeature('id', 'resources_id_seq'),
            Resource::class
        );
    }

    protected function getObjects(Adapter $adapter): array
    {
        return DataModel::getResources($adapter, $this->table);
    }
}
