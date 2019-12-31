<?php

declare(strict_types=1);

use Psr\Container\ContainerInterface;
use Zend\Expressive\Application;
use Zend\Expressive\MiddlewareFactory;

/*
 * Setup routes with a single request method:
 *
 * $app->get('/', App\Handler\HomePageHandler::class, 'home');
 * $app->post('/album', App\Handler\AlbumCreateHandler::class, 'album.create');
 * $app->put('/album/:id', App\Handler\AlbumUpdateHandler::class, 'album.put');
 * $app->patch('/album/:id', App\Handler\AlbumUpdateHandler::class, 'album.patch');
 * $app->delete('/album/:id', App\Handler\AlbumDeleteHandler::class, 'album.delete');
 *
 * Or with multiple request methods:
 *
 * $app->route('/contact', App\Handler\ContactHandler::class, ['GET', 'POST', ...], 'contact');
 *
 * Or handling all request methods:
 *
 * $app->route('/contact', App\Handler\ContactHandler::class)->setName('contact');
 *
 * or:
 *
 * $app->route(
 *     '/contact',
 *     App\Handler\ContactHandler::class,
 *     Zend\Expressive\Router\Route::HTTP_METHOD_ANY,
 *     'contact'
 * );
 */

return function (Application $app, MiddlewareFactory $factory, ContainerInterface $container): void {
    $app->route('/access/api/resources[/{id:\d+}]', [
        Zend\Expressive\Session\SessionMiddleware::class,
        Zend\Expressive\Authentication\AuthenticationMiddleware::class,
        App\Handler\API\ResourceHandler::class,
    ], ['GET', 'POST', 'PUT', 'DELETE'], 'api.resources');
    $app->route('/access/api/roles[/{id:\d+}]', [
        Zend\Expressive\Session\SessionMiddleware::class,
        Zend\Expressive\Authentication\AuthenticationMiddleware::class,
        App\Handler\API\RoleHandler::class,
    ], ['GET', 'POST', 'PUT', 'DELETE'], 'api.roles');
    $app->route('/access/api/users[/{id:\d+}]', [
        Zend\Expressive\Session\SessionMiddleware::class,
        Zend\Expressive\Authentication\AuthenticationMiddleware::class,
        App\Handler\API\UserHandler::class,
    ], ['GET', 'POST', 'PUT', 'DELETE'], 'api.users');

    $app->get('/', App\Handler\HomePageHandler::class, 'home');

    $app->route('/login', [
        Zend\Expressive\Session\SessionMiddleware::class,
        Zend\Expressive\Csrf\CsrfMiddleware::class,
        App\Handler\LoginHandler::class,
    ], ['GET', 'POST'], 'login');

    $app->route('/password', App\Handler\PasswordHandler::class, ['GET', 'POST'], 'password');
    $app->route('/password/recovery-code/{uuid}', App\Handler\RecoveryCodeHandler::class, ['GET', 'POST'], 'password.code');

    $app->get('/profile', [
        Zend\Expressive\Session\SessionMiddleware::class,
        Zend\Expressive\Authentication\AuthenticationMiddleware::class,
        App\Handler\ProfileHandler::class,
    ], 'profile');

    $app->get('/access/admin', [
        Zend\Expressive\Session\SessionMiddleware::class,
        Zend\Expressive\Authentication\AuthenticationMiddleware::class,
        App\Handler\Admin\HomeHandler::class,
    ], 'admin');
    $app->get('/access/admin/users', [
        Zend\Expressive\Session\SessionMiddleware::class,
        Zend\Expressive\Authentication\AuthenticationMiddleware::class,
        App\Handler\Admin\UsersHandler::class,
    ], 'admin.users');
    $app->get('/access/admin/roles', [
        Zend\Expressive\Session\SessionMiddleware::class,
        Zend\Expressive\Authentication\AuthenticationMiddleware::class,
        App\Handler\Admin\RolesHandler::class,
    ], 'admin.roles');
    $app->get('/access/admin/resources', [
        Zend\Expressive\Session\SessionMiddleware::class,
        Zend\Expressive\Authentication\AuthenticationMiddleware::class,
        App\Handler\Admin\ResourcesHandler::class,
    ], 'admin.resources');

    $app->get('/logout', [
        Zend\Expressive\Session\SessionMiddleware::class,
        Zend\Expressive\Authentication\AuthenticationMiddleware::class,
        App\Handler\LoginHandler::class,
    ], 'logout');
};
