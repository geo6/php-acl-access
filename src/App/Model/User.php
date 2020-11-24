<?php

declare(strict_types=1);

namespace App\Model;

use JsonSerializable;

class User implements JsonSerializable
{
    /** @var int */
    public $id;
    /** @var string */
    public $login;
    /** @var string */
    public $fullname;
    /** @var string */
    public $email;
    /** @var int|null */
    public $redirect;

    /** @var array */
    protected $roles = [];

    public function getRoles(): array
    {
        return $this->roles;
    }

    public function addRole(Role $role): void
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
            'redirect' => $this->redirect,
            'roles'    => $this->getRoles(),
        ];
    }
}
