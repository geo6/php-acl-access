<?php

declare(strict_types=1);

namespace App\Handler;

use App\DataModel;
use App\Middleware\DbMiddleware;
use App\Model\Resource;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Permissions\Acl\AclInterface;
use Mezzio\Authentication\UserInterface;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ProfileHandler implements RequestHandlerInterface
{
    /** @var TemplateRendererInterface */
    private $renderer;

    /** @var string[] */
    private $tables;

    public function __construct(TemplateRendererInterface $renderer, array $tables)
    {
        $this->renderer = $renderer;
        $this->tables = $tables;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $adapter = $request->getAttribute(DbMiddleware::class);
        $user = $request->getAttribute(UserInterface::class);
        $acl = $request->getAttribute(AclInterface::class);

        $resources = DataModel::getResources($adapter, $this->tables['resource']);
        $applications = array_values(array_filter($resources, function (Resource $resource) use ($acl, $user) {
            return preg_match('/^(?!home-).+$/', $resource->name) === 1
                && $acl->isAllowed($user->getIdentity(), $resource->name);
        }));

        return new HtmlResponse($this->renderer->render(
            'app::profile',
            [
                'applications' => $applications,
            ]
        ));
    }
}
