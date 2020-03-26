<?php

declare(strict_types=1);

namespace App\Handler\Exception;

use Laminas\Log\Logger;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

class LoginException extends AbstractException
{
    /** @var string */
    private $username;

    public function __construct(
        ?string $username,
        int $code = 0,
        ?Throwable $previous = null,
        ?ServerRequestInterface $request = null
    ) {
        $this->username = $username;

        parent::__construct('Login failed.', $code, $previous);

        $this->log('Login failed ({username}).', Logger::WARN, $request);
    }

    public function getData(): array
    {
        return [
            'username' => $this->username,
        ];
    }
}
