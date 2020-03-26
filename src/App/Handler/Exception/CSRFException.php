<?php

declare(strict_types=1);

namespace App\Handler\Exception;

use Laminas\Log\Logger;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

class CSRFException extends AbstractException
{
    private $username;

    public function __construct(
        ?string $username,
        int $code = 0,
        ?Throwable $previous = null,
        ?ServerRequestInterface $request = null
    ) {
        $this->username = $username;

        parent::__construct('Invalid CSRF token.', $code, $previous);

        $this->log('Invalid CSRF token ({username}).', Logger::CRIT, $request);
    }

    public function getData(): array
    {
        return [
            'username' => $this->username,
        ];
    }
}
