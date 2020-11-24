<?php

declare(strict_types=1);

namespace App\Model;

use JsonSerializable;

class Role implements JsonSerializable
{
    /** @var int */
    public $id;
    /** @var string */
    public $name;
    /** @var int */
    public $priority;

    public function __toString(): string
    {
        return $this->name;
    }

    public function jsonSerialize(): array
    {
        return [
            'id'       => $this->id,
            'name'     => $this->name,
            'priority' => $this->priority,
        ];
    }
}
