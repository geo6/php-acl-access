<?php

declare(strict_types=1);

use Mezzio\Authentication\AuthenticationInterface;
use Mezzio\Authentication\Session\PhpSession;
use Mezzio\Authentication\UserRepository\PdoDatabase;
use Mezzio\Authentication\UserRepositoryInterface;

return [

    'dependencies' => [
        'aliases' => [
            AuthenticationInterface::class => PhpSession::class,
            UserRepositoryInterface::class => PdoDatabase::class,
        ],
    ],

    'authentication' => [
        'username' => 'login',
        'password' => 'password',
        'redirect' => 'login',
    ],

];
