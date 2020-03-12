<?php

declare(strict_types=1);

use Mezzio\Application;
use Mezzio\MiddlewareFactory;
use Psr\Container\ContainerInterface;

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
 *     Mezzio\Router\Route::HTTP_METHOD_ANY,
 *     'contact'
 * );
 */

return function (Application $app, MiddlewareFactory $factory, ContainerInterface $container): void {
    $app->route('/access/api/resources[/{id:\d+}]', [
        Mezzio\Session\SessionMiddleware::class,
        Mezzio\Authentication\AuthenticationMiddleware::class,
        App\Handler\API\ResourceHandler::class,
    ], ['GET', 'POST', 'PUT', 'DELETE'], 'api.resources');
    $app->route('/access/api/roles[/{id:\d+}]', [
        Mezzio\Session\SessionMiddleware::class,
        Mezzio\Authentication\AuthenticationMiddleware::class,
        App\Handler\API\RoleHandler::class,
    ], ['GET', 'POST', 'PUT', 'DELETE'], 'api.roles');
    $app->route('/access/api/users[/{id:\d+}]', [
        Mezzio\Session\SessionMiddleware::class,
        Mezzio\Authentication\AuthenticationMiddleware::class,
        App\Handler\API\UserHandler::class,
    ], ['GET', 'POST', 'PUT', 'DELETE'], 'api.users');

    $app->get('/', App\Handler\HomePageHandler::class, 'home');

    $app->route('/login', [
        Mezzio\Session\SessionMiddleware::class,
        Mezzio\Csrf\CsrfMiddleware::class,
        App\Handler\LoginHandler::class,
    ], ['GET', 'POST'], 'login');

    $app->route('/password', App\Handler\PasswordHandler::class, ['GET', 'POST'], 'password');
    $app->route('/password/recovery-code/{uuid}', App\Handler\RecoveryCodeHandler::class, ['GET', 'POST'], 'password.code');

    $app->get('/profile', [
        Mezzio\Session\SessionMiddleware::class,
        Mezzio\Authentication\AuthenticationMiddleware::class,
        App\Handler\ProfileHandler::class,
    ], 'profile');

    $app->get('/access/admin', [
        Mezzio\Session\SessionMiddleware::class,
        Mezzio\Authentication\AuthenticationMiddleware::class,
        App\Handler\Admin\HomeHandler::class,
    ], 'admin');
    $app->get('/access/admin/users', [
        Mezzio\Session\SessionMiddleware::class,
        Mezzio\Authentication\AuthenticationMiddleware::class,
        App\Handler\Admin\UsersHandler::class,
    ], 'admin.users');
    $app->get('/access/admin/roles', [
        Mezzio\Session\SessionMiddleware::class,
        Mezzio\Authentication\AuthenticationMiddleware::class,
        App\Handler\Admin\RolesHandler::class,
    ], 'admin.roles');
    $app->get('/access/admin/resources', [
        Mezzio\Session\SessionMiddleware::class,
        Mezzio\Authentication\AuthenticationMiddleware::class,
        App\Handler\Admin\ResourcesHandler::class,
    ], 'admin.resources');

    $app->get('/logout', [
        Mezzio\Session\SessionMiddleware::class,
        Mezzio\Authentication\AuthenticationMiddleware::class,
        App\Handler\LoginHandler::class,
    ], 'logout');
};
