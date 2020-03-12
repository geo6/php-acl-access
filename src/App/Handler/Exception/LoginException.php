<?php

declare(strict_types=1);

namespace App\Handler\Exception;

use Laminas\Log\Logger;

class LoginException extends AbstractException
{
    /** @var string */
    private $username;

    public function __construct(?string $username, int $code = 0, Throwable $previous = null)
    {
        $this->username = $username;

        parent::__construct('Login failed.', $code, $previous);

        $this->log('Login failed ({username}).', Logger::WARN);
    }

    public function getData(): array
    {
        return [
            'username' => $this->username,
        ];
    }
}
