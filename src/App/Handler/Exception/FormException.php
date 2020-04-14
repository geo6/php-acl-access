<?php

declare(strict_types=1);

namespace App\Handler\Exception;

use Throwable;

class FormException extends \Exception
{
    /** @var string */
    private $field;

    public function __construct(string $field, string $message = '', int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);

        $this->field = $field;
    }

    public function getField(): string
    {
        return $this->field;
    }
}
