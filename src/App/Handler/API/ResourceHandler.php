<?php

declare(strict_types=1);

namespace App\Handler\API;

use App\DataModel;
use App\Model\Resource;
use Geo6\Zend\Log\Log;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Sql\TableIdentifier;
use Laminas\Db\TableGateway\Feature\SequenceFeature;
use Laminas\Log\Logger;

class ResourceHandler extends DefaultHandler
{
    public function __construct(TableIdentifier $table)
    {
        $this->init(
            $table,
            new SequenceFeature('id', 'resources_id_seq'),
            Resource::class
        );
    }

    protected function insert(Adapter $adapter, array $data): Resource
    {
        $resource = parent::insert($adapter, $data);

        Log::write(
            sprintf('data/log/%s.log', date('Ym')),
            'New resource "{resource}" created.',
            ['resource' => $resource->name],
            Logger::NOTICE
        );

        return $resource;
    }

    protected function delete(Adapter $adapter, $resource): Resource
    {
        $resource = parent::delete($adapter, $resource);

        Log::write(
            sprintf('data/log/%s.log', date('Ym')),
            'Resource "{resource}" deleted.',
            ['resource' => $resource->name],
            Logger::WARN
        );

        return $resource;
    }

    protected function getObjects(Adapter $adapter): array
    {
        return DataModel::getResources($adapter, $this->table);
    }
}
