<?php

declare(strict_types=1);

namespace App\Model;

use JsonSerializable;

class Resource implements JsonSerializable
{
    /** @var int */
    public $id;
    /** @var string */
    public $name;
    /** @var string */
    public $path;

    public function jsonSerialize(): array
    {
        return [
            'id'   => $this->id,
            'name' => $this->name,
            'path' => $this->path,
        ];
    }
}
