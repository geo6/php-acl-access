<?php

declare(strict_types=1);

namespace App\Handler\API;

use App\DataModel;
use App\Handler\Exception\FormException;
use App\Model\Resource;
use Geo6\Laminas\Log\Log;
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
        $resources = DataModel::getResources($adapter, $this->table);

        $name = $data['name'];
        $checkLogin = array_filter($resources, function (Resource $resource) use ($name): bool {
            return $resource->name === $name;
        });
        if (count($checkLogin) > 0) {
            throw new FormException('name', 'Name must be unique.');
        }

/** @var \App\Model\Resource */ $resource = parent::insert($adapter, $data);

        Log::write(
            sprintf('data/log/%s-admin.log', date('Ym')),
            'New resource "{resource}" created.',
            ['resource' => $resource->name],
            Logger::NOTICE,
            $this->request
        );

        return $resource;
    }

    protected function delete(Adapter $adapter, $resource): Resource
    {
/** @var \App\Model\Resource */ $resource = parent::delete($adapter, $resource);

        Log::write(
            sprintf('data/log/%s-admin.log', date('Ym')),
            'Resource "{resource}" deleted.',
            ['resource' => $resource->name],
            Logger::WARN,
            $this->request
        );

        return $resource;
    }

    protected function getObjects(Adapter $adapter): array
    {
        return DataModel::getResources($adapter, $this->table);
    }
}
