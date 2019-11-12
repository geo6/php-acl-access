<?php

declare(strict_types=1);

namespace App\Handler;

use Geo6\Zend\Log\Log;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Diactoros\Response\RedirectResponse;
use Zend\Diactoros\Response\TextResponse;
use Zend\Expressive\Authentication\Session\PhpSession;
use Zend\Expressive\Authentication\UserInterface;
use Zend\Expressive\Csrf\CsrfMiddleware;
use Zend\Expressive\Router\RouteResult;
use Zend\Expressive\Router\RouterInterface;
use Zend\Expressive\Session\SessionInterface;
use Zend\Expressive\Session\SessionMiddleware;
use Zend\Expressive\Template\TemplateRendererInterface;
use Zend\Log\Logger;

/**
 * @see https://docs.zendframework.com/zend-expressive-authentication-session/v1/login-handler/
 */
class LoginHandler implements RequestHandlerInterface
{
    private const REDIRECT_ATTRIBUTE = 'authentication:redirect';

    /** @var array */
    private $config;

    /** @var PhpSession */
    private $adapter;

    /** @var TemplateRendererInterface */
    private $renderer;

    /** @var RouterInterface */
    private $router;

    public function __construct(TemplateRendererInterface $renderer, RouterInterface $router, PhpSession $adapter, array $config)
    {
        $this->renderer = $renderer;
        $this->router = $router;
        $this->adapter = $adapter;
        $this->config = $config;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $session = $request->getAttribute(SessionMiddleware::SESSION_ATTRIBUTE);
        $route = ($request->getAttribute(RouteResult::class))->getMatchedRouteName();
        $guard = $request->getAttribute(CsrfMiddleware::GUARD_ATTRIBUTE);

        // Logout
        if ($route === 'logout') {
            $session->clear();
            $session->regenerate();

            return new RedirectResponse($this->router->generateUri('login'));
        }

        $redirect = $this->getRedirect($request, $session);

        // Handle submitted credentials
        if ('POST' === $request->getMethod()) {
            return $this->handleLoginAttempt($request, $session, $redirect);
        }

        // Display initial login form
        $token = $guard->generateToken();

        $session->set(self::REDIRECT_ATTRIBUTE, $redirect);

        return new HtmlResponse($this->renderer->render(
            'app::login',
            [
                '__csrf' => $token,
            ]
        ));
    }

    private function getRedirect(
        ServerRequestInterface $request,
        SessionInterface $session
    ): string {
        $redirect = $session->get(self::REDIRECT_ATTRIBUTE);
        $user = $session->get(UserInterface::class);

        if (!$redirect) {
            $redirect = $request->getHeaderLine('Referer');
            if (in_array($redirect, ['', '/login'], true)) {
                $redirect = $user['details']['redirect'] ?? $this->router->generateUri('profile');
            }
        }

        return $redirect;
    }

    private function handleLoginAttempt(
        ServerRequestInterface $request,
        SessionInterface $session,
        string $redirect
    ): ResponseInterface {
        $guard = $request->getAttribute(CsrfMiddleware::GUARD_ATTRIBUTE);
        $session = $request->getAttribute(SessionMiddleware::SESSION_ATTRIBUTE);

        $query = $request->getParsedBody();

        $usernameField = $this->config['authentication']['username'];

        // User session takes precedence over user/pass POST in
        // the auth adapter so we remove the session prior
        // to auth attempt
        $session->unset(UserInterface::class);

        // Check CSRF
        $token = $query['__csrf'] ?? '';
        if (strlen($token) === 0 || !$guard->validateToken($token)) {
            Log::write(
                sprintf('data/log/%s.log', date('Ym')),
                'Invalid CSRF token ({username}).',
                ['username' => $query[$usernameField] ?? null],
                Logger::CRIT
            );

            return new TextResponse('Token not provided (or expired). Please try to login again!', 412);
        }

        // Login was successful
        $user = $this->adapter->authenticate($request);
        if (!is_null($user)) {
            $session->unset(self::REDIRECT_ATTRIBUTE);

            Log::write(
                sprintf('data/log/%s.log', date('Ym')),
                'Login successful ({username}).',
                ['username' => $user->getIdentity()],
                Logger::INFO
            );

            return new RedirectResponse($redirect);
        }

        // Login failed
        Log::write(
            sprintf('data/log/%s.log', date('Ym')),
            'Login failed ({username}).',
            ['username' => $query[$usernameField] ?? null],
            Logger::WARN
        );

        return new HtmlResponse($this->renderer->render(
            'app::login',
            [
                'error' => true,
            ]
        ));
    }
}
