<?php

declare(strict_types=1);

namespace App\Model;

use JsonSerializable;

class User implements JsonSerializable
{
    public $id;
    public $login;
    public $fullname;
    public $email;

    protected $roles = [];

    public function getRoles()
    {
        return $this->roles;
    }

    public function addRole(Role $role)
    {
        $this->roles[] = $role;
    }

    public function jsonSerialize(): array
    {
        return [
            'id'       => $this->id,
            'login'    => $this->login,
            'fullname' => $this->fullname,
            'email'    => $this->email,
            'roles'    => $this->getRoles(),
        ];
    }
}