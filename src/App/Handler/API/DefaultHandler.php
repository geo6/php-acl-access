<?php

declare(strict_types=1);

namespace App\Handler\API;

use App\Middleware\DbMiddleware;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\RowGateway\RowGateway;
use Laminas\Db\TableGateway\Feature\SequenceFeature;
use Laminas\Db\TableGateway\TableGateway;
use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Hydrator\ReflectionHydrator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use stdClass;

abstract class DefaultHandler implements RequestHandlerInterface
{
    protected $table;
    protected $sequenceFeature;
    protected $class;

    public function init(string $table, SequenceFeature $sequenceFeature, string $class)
    {
        $this->table = $table;
        $this->sequenceFeature = $sequenceFeature;
        $this->class = $class;
    }

    protected static function toArray($object): array
    {
        return $object->jsonSerialize();
    }

    abstract protected function getObjects(Adapter $adapter): array;

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $adapter = $request->getAttribute(DbMiddleware::class);

        $id = $request->getAttribute('id');
        $data = $request->getParsedBody();

        $objects = $this->getObjects($adapter);

        if (!is_null($id)) {
            $filter = array_filter($objects, function ($object) use ($id) {
                return $object->id === intval($id);
            });

            if (count($filter) === 1) {
                $object = current($filter);
            }
        }

        switch ($request->getMethod()) {
            case 'GET':
                if (is_null($id)) {
                    return new JsonResponse($objects);
                } elseif (isset($object)) {
                    return new JsonResponse($object);
                } else {
                    return new JsonResponse(new stdClass(), 404);
                }
                break;
            case 'POST':
                if (!is_null($data)) {
                    $object = $this->insert($adapter, $data);

                    return new JsonResponse($object);
                } else {
                    return new JsonResponse(new stdClass(), 404);
                }
                break;
            case 'PUT':
                if (isset($object) && !is_null($data)) {
                    $object = $this->update($adapter, $object, $data);

                    return new JsonResponse($object);
                } else {
                    return new JsonResponse(new stdClass(), 404);
                }
                break;
            case 'DELETE':
                if (isset($object)) {
                    $object = $this->delete($adapter, $object);

                    return new JsonResponse($object);
                } else {
                    return new JsonResponse(new stdClass(), 404);
                }

                break;
        }
    }

    protected function insert(Adapter $adapter, array $data)
    {
        $tableGateway = new TableGateway($this->table, $adapter, [
            $this->sequenceFeature,
        ]);

        $id = $tableGateway->insert($data);

        $result = $tableGateway->select(['id' => $id])->toArray();

        return (new ReflectionHydrator())->hydrate(current($result), new $this->class());
    }

    protected function update(Adapter $adapter, $object, array $data)
    {
        $rowGateway = new RowGateway('id', $this->table, $adapter);
        $rowGateway->populate(static::toArray($object), true);

        foreach ($data as $key => $value) {
            if (property_exists($this->class, $key) === true) {
                $rowGateway->{$key} = $value;
            }
        }

        $rowGateway->save();

        return (new ReflectionHydrator())->hydrate($rowGateway->toArray(), new $this->class());
    }

    protected function delete(Adapter $adapter, $object)
    {
        $rowGateway = new RowGateway('id', $this->table, $adapter);
        $rowGateway->populate(static::toArray($object), true);

        $rowGateway->delete();

        return $object;
    }
}
