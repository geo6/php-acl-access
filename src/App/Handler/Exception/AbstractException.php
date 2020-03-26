<?php

declare(strict_types=1);

namespace App\Handler\Exception;

use Geo6\Laminas\Log\Log;
use Laminas\Log\Logger;
use Psr\Http\Message\ServerRequestInterface;

abstract class AbstractException extends \Exception
{
    abstract public function getData(): array;

    public function log(string $message, int $priority = Logger::INFO, ?ServerRequestInterface $request = null): void
    {
        Log::write(
            sprintf('data/log/%s-login.log', date('Ym')),
            $message,
            $this->getData(),
            $priority,
            $request
        );
    }
}
