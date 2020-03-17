<?php

declare(strict_types=1);

namespace App\Handler\API;

use App\DataModel;
use App\Middleware\DbMiddleware;
use ErrorException;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Sql\Sql;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class AccessHandler implements RequestHandlerInterface
{
    /** @var string */
    private $table;

    /** @var string */
    private $tableResource;

    /** @var string */
    private $tableRole;

    public function __construct(string $table, string $tableResource, string $tableRole)
    {
        $this->table = $table;
        $this->tableResource = $tableResource;
        $this->tableRole = $tableRole;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $adapter = $request->getAttribute(DbMiddleware::class);

        $type = $request->getAttribute('type');
        $id = $request->getAttribute('id');
        $data = $request->getParsedBody();

        switch ($request->getMethod()) {
            case 'GET':
                $objects = $this->get($adapter, $type, !is_null($id) ? intval($id) : null);

                return new JsonResponse($objects);
            case 'PUT':
                if (!is_null($type) && !is_null($id)) {
                    $this->delete($adapter, $type, intval($id));
                    $this->insert($adapter, $type, intval($id), $data);

                    $objects = $this->get($adapter, $type, !is_null($id) ? intval($id) : null);

                    return new JsonResponse($objects);
                } else {
                    return new JsonResponse([], 404);
                }
                break;
        }
    }

    private function get(Adapter $adapter, ?string $type, ?int $id): array
    {
        $objects = DataModel::getRolesResources($adapter, $this->table, $this->tableResource, $this->tableRole);

        if (!is_null($type) && !is_null($id)) {
            $objects = array_filter($objects, function ($object) use ($type, $id) {
                if ($type === 'resource') {
                    return $object['resource']->id === $id;
                } elseif ($type === 'role') {
                    return $object['role']->id === $id;
                }

                return false;
            });

            $objects = array_values($objects);
        }

        return $objects;
    }

    private function delete(Adapter $adapter, string $type, int $id)
    {
        $sql = new Sql($adapter);

        $delete = $sql->delete($this->table);

        if ($type === 'resource') {
            $delete->where->equalTo('id_resource', $id);
        } elseif ($type === 'role') {
            $delete->where->equalTo('id_role', $id);
        } else {
            throw new ErrorException(sprintf('Invalid type "%s".', $type));
        }

        return $adapter->query($sql->buildSqlString($delete), $adapter::QUERY_MODE_EXECUTE);
    }

    private function insert(Adapter $adapter, string $type, int $id, array $data)
    {
        $sql = new Sql($adapter);

        $allow = array_filter($data, function ($value) {
            return intval($value) === 1;
        });

        foreach ($allow as $key => $value) {
            $insert = $sql->insert($this->table);

            if ($type === 'resource' && preg_match('/^role\[(\d+)\]$/', $key, $matches) === 1) {
                $insert->values([
                    'id_resource' => $id,
                    'id_role'     => intval($matches[1]),
                ]);
                $adapter->query($sql->buildSqlString($insert), $adapter::QUERY_MODE_EXECUTE);
            } elseif ($type === 'role' && preg_match('/^resource\[(\d+)\]$/', $key, $matches) === 1) {
                $insert->values([
                    'id_resource' => intval($matches[1]),
                    'id_role'     => $id,
                ]);
                $adapter->query($sql->buildSqlString($insert), $adapter::QUERY_MODE_EXECUTE);
            }
        }
    }
}
