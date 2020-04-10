<?php

declare(strict_types=1);

namespace App\Handler;

use App\DataModel;
use App\Middleware\DbMiddleware;
use App\Model\Resource;
use App\UserRepository;
use Laminas\Db\Sql\TableIdentifier;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Permissions\Acl\AclInterface;
use Mezzio\Authentication\UserInterface;
use Mezzio\Router\RouterInterface;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ProfileHandler implements RequestHandlerInterface
{
    /** @var array */
    private $configAuthenticationPDO;

    /** @var TemplateRendererInterface */
    private $renderer;

    /** @var RouterInterface */
    private $router;

    /** @var TableIdentifier */
    private $tableResource;

    public function __construct(TemplateRendererInterface $renderer, RouterInterface $router, TableIdentifier $tableResource, array $configAuthenticationPDO)
    {
        $this->configAuthenticationPDO = $configAuthenticationPDO;
        $this->renderer = $renderer;
        $this->router = $router;
        $this->tableResource = $tableResource;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $adapter = $request->getAttribute(DbMiddleware::class);
        $user = $request->getAttribute(UserInterface::class);
        $acl = $request->getAttribute(AclInterface::class);

        $login = $request->getAttribute('login');

        if (!is_null($login)) {
            if ($acl->isAllowed($user->getIdentity(), 'profile') !== true) {
                return new RedirectResponse($this->router->generateUri('profile'));
            }

            $repository = new UserRepository($this->configAuthenticationPDO);
            $profile = $repository->search($this->configAuthenticationPDO['field']['identity'], $login);

            if (is_null($profile)) {
                return new RedirectResponse($this->router->generateUri('profile'));
            }
        } else {
            $profile = $user;
        }

        $resources = DataModel::getResources($adapter, $this->tableResource);
        $applications = array_values(array_filter($resources, function (Resource $resource) use ($acl, $profile) {
            return preg_match('/^(?!home-).+$/', $resource->name) === 1
                && $acl->isAllowed($profile->getIdentity(), $resource->name);
        }));
        $homepages = array_values(array_filter($resources, function (Resource $resource) use ($acl, $profile) {
            return preg_match('/^home-.+$/', $resource->name) === 1
                && $acl->isAllowed($profile->getIdentity(), $resource->name);
        }));

        return new HtmlResponse($this->renderer->render(
            'app::profile',
            [
                'profile' => $profile,
                'access'  => [
                    'applications' => $applications,
                    'homepages'    => $homepages,
                ],
            ]
        ));
    }
}
