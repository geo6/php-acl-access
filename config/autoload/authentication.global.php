<?php

declare(strict_types=1);

use Zend\Expressive\Authentication\AuthenticationInterface;
use Zend\Expressive\Authentication\Session\PhpSession;
use Zend\Expressive\Authentication\UserRepositoryInterface;
use Zend\Expressive\Authentication\UserRepository\PdoDatabase;

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
