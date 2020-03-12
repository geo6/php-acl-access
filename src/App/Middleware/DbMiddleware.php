<?php

declare(strict_types=1);

namespace App\Middleware;

use Laminas\Db\Adapter\Adapter;
use PDO;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class DbMiddleware implements MiddlewareInterface
{
    /** @var array */
    private $connection;

    public function __construct(array $connection)
    {
        $this->connection = $connection;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $adapter = new Adapter(
            array_merge([
                'driver'         => 'Pdo_Pgsql',
                'driver_options' => [
                    PDO::ATTR_STRINGIFY_FETCHES => false,
                ],
            ], $this->connection)
        );

        return $handler->handle($request->withAttribute(self::class, $adapter));
    }
}
