<?php

declare(strict_types=1);

namespace App\Handler\Exception;

use Laminas\Log\Logger;

class CSRFException extends AbstractException
{
    private $username;

    public function __construct(?string $username, int $code = 0, Throwable $previous = null)
    {
        $this->username = $username;

        parent::__construct('Invalid CSRF token.', $code, $previous);

        $this->log('Invalid CSRF token ({username}).', Logger::CRIT);
    }

    public function getData(): array
    {
        return [
            'username' => $this->username,
        ];
    }
}
