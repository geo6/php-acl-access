<?php

declare(strict_types=1);

namespace App\Handler\Admin;

use App\DataModel;
use App\Middleware\DbMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Expressive\Template\TemplateRendererInterface;

class UsersHandler implements RequestHandlerInterface
{
    /** @var TemplateRendererInterface */
    private $renderer;

    public function __construct(TemplateRendererInterface $renderer)
    {
        $this->renderer = $renderer;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $adapter = $request->getAttribute(DbMiddleware::class);

        return new HtmlResponse($this->renderer->render(
            'app::admin/users',
            [
                'users' => DataModel::getUsers($adapter),
                'roles' => DataModel::getRoles($adapter),
            ]
        ));
    }
}
