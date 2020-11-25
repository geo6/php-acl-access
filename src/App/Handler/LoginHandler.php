<?php

declare(strict_types=1);

namespace App\Handler;

use App\BruteForceProtection;
use App\Handler\Exception\CSRFException;
use App\Handler\Exception\LoginException;
use App\Handler\Exception\ReCAPTCHAException;
use DateInterval;
use DateTime;
use ErrorException;
use Exception;
use Geo6\Laminas\Log\Log;
use GuzzleHttp\Client;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Diactoros\Response\TextResponse;
use Laminas\Log\Logger;
use Mezzio\Authentication\Session\PhpSession;
use Mezzio\Authentication\UserInterface;
use Mezzio\Csrf\CsrfMiddleware;
use Mezzio\Router\RouteResult;
use Mezzio\Router\RouterInterface;
use Mezzio\Session\SessionInterface;
use Mezzio\Session\SessionMiddleware;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * @see https://docs.zendframework.com/zend-expressive-authentication-session/v1/login-handler/
 */
class LoginHandler implements RequestHandlerInterface
{
    private const REDIRECT_ATTRIBUTE = 'authentication:redirect';
    private const ENABLE_RECAPTCHA = 3;
    private const ENABLE_LOCK = 5;
    private const LOCK_INTERVAL = 'PT1H';

    /** @var array */
    private $config;

    /** @var PhpSession */
    private $adapter;

    /** @var TemplateRendererInterface */
    private $renderer;

    /** @var RouterInterface */
    private $router;

    public function __construct(
        TemplateRendererInterface $renderer,
        RouterInterface $router,
        PhpSession $adapter,
        array $config
    ) {
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

        $protection = new BruteForceProtection($request);

        $reCAPTCHA = ($protection->getCount() >= self::ENABLE_RECAPTCHA && !is_null($this->config['reCAPTCHA']) ? $this->config['reCAPTCHA']['key'] : false);

        // Handle submitted credentials
        if ('POST' === $request->getMethod() && $protection->isLocked() === false) {
            try {
                $user = $this->handleLoginAttempt($request, $reCAPTCHA !== false);

                Log::write(
                    sprintf('data/log/%s-login.log', date('Ym')),
                    'Login successful ({username}).',
                    ['username' => $user->getIdentity()],
                    Logger::INFO,
                    $request
                );

                $protection->clear();

                $redirect = $user->getDetail('redirect') ?? $this->router->generateUri('profile');

                return new RedirectResponse($redirect);
            } catch (CSRFException $e) {
                return new TextResponse('Token not provided (or expired). Please try to login again!', 412);
            } catch (ReCAPTCHAException $e) {
                return new HtmlResponse($this->renderer->render('app::login', [
                    '__csrf'    => $guard->generateToken(),
                    'reCAPTCHA' => $protection->getCount() >= self::ENABLE_RECAPTCHA && !is_null($this->config['reCAPTCHA']) ? $this->config['reCAPTCHA']['key'] : null,
                    'error'     => $e->getMessage(),
                    'locked'    => $protection->isLocked(),
                ]));
            } catch (LoginException $e) {
                $protection->increment();

                if ($protection->getCount() >= self::ENABLE_LOCK) {
                    $protection->lockUntil((new DateTime())->add(new DateInterval(self::LOCK_INTERVAL)));
                }

                return new HtmlResponse($this->renderer->render('app::login', [
                    '__csrf'    => $guard->generateToken(),
                    'reCAPTCHA' => $protection->getCount() >= self::ENABLE_RECAPTCHA && !is_null($this->config['reCAPTCHA']) ? $this->config['reCAPTCHA']['key'] : null,
                    'error'     => $e->getMessage(),
                    'locked'    => $protection->isLocked(),
                ]));
            } catch (Exception $e) {
                return new HtmlResponse($this->renderer->render('app::login', [
                    '__csrf'    => $guard->generateToken(),
                    'reCAPTCHA' => $protection->getCount() >= self::ENABLE_RECAPTCHA && !is_null($this->config['reCAPTCHA']) ? $this->config['reCAPTCHA']['key'] : null,
                    'error'     => $e->getMessage(),
                    'locked'    => $protection->isLocked(),
                ]));
            }
        }

        // $redirect = $this->getRedirect($request, $session);
        // $session->set(self::REDIRECT_ATTRIBUTE, $redirect);

        return new HtmlResponse($this->renderer->render('app::login', [
            '__csrf'    => $guard->generateToken(),
            'reCAPTCHA' => $protection->getCount() >= self::ENABLE_RECAPTCHA && !is_null($this->config['reCAPTCHA']) ? $this->config['reCAPTCHA']['key'] : null,
            'locked'    => $protection->isLocked(),
        ]));
    }

    // private function getRedirect(
    //     ServerRequestInterface $request,
    //     SessionInterface $session
    // ): string {
    //     $redirect = $session->get(self::REDIRECT_ATTRIBUTE);
    //     $user = $session->get(UserInterface::class);

    //     if (!$redirect) {
    //         $redirect = $request->getHeaderLine('Referer');
    //         if (in_array($redirect, ['', '/login'], true)) {
    //             $redirect = $user['details']['redirect'] ?? $this->router->generateUri('profile');
    //         }
    //     }

    //     return $redirect;
    // }

    private function handleLoginAttempt(
        ServerRequestInterface $request,
        bool $reCAPTCHA
    ): UserInterface {
        $guard = $request->getAttribute(CsrfMiddleware::GUARD_ATTRIBUTE);
        $session = $request->getAttribute(SessionMiddleware::SESSION_ATTRIBUTE);

        $query = $request->getParsedBody();

        /** @var string */ $usernameField = $this->config['authentication']['username'];

        // User session takes precedence over user/pass POST in
        // the auth adapter so we remove the session prior
        // to auth attempt
        $session->unset(UserInterface::class);

        // Check CSRF
        $token = $query['__csrf'] ?? '';
        if (strlen($token) === 0 || !$guard->validateToken($token)) {
            throw new CSRFException($query[$usernameField] ?? null, 0, null, $request);
        }

        // Check reCAPTCHA
        if ($reCAPTCHA === true) {
            $response = self::reCAPTCHA($this->config['reCAPTCHA']['secret'], $query['reCAPTCHA'] ?? '');

            $threshold = $this->config['reCAPTCHA']['threshold'] ?? 0.5;

            if ($response['success'] !== true || $response['score'] < $threshold) {
                throw new ReCAPTCHAException($query[$usernameField] ?? null, $response['score'], $threshold, 0, null, $request);
            }
        }

        $user = $this->adapter->authenticate($request);

        // Login failed
        if (is_null($user)) {
            throw new LoginException($query[$usernameField] ?? null, 0, null, $request);
        }

        $session->unset(self::REDIRECT_ATTRIBUTE);

        return $user;
    }

    private static function reCAPTCHA(string $secret, string $token): array
    {
        $response = (new Client())->request(
            'POST',
            'https://www.google.com/recaptcha/api/siteverify',
            [
                'form_params' => [
                    'secret'   => $secret,
                    'response' => $token,
                ],
            ]
        );

        if ($response->getStatusCode() !== 200) {
            throw new ErrorException('reCAPTCHA request failed.');
        }

        $json = json_decode((string) $response->getBody(), true);

        if (isset($json['error-codes']) && count($json['error-codes']) > 0) {
            $message = 'Issue(s) with reCAPTCHA request:'.PHP_EOL;

            foreach ($json['error-codes'] as $code) {
                switch ($code) {
                    case 'missing-input-secret':
                        $message .= 'The secret parameter is missing.'.PHP_EOL;
                        break;
                    case 'invalid-input-secret':
                        $message .= 'The secret parameter is invalid or malformed.'.PHP_EOL;
                        break;
                    case 'missing-input-response':
                        $message .= 'The response parameter is missing.'.PHP_EOL;
                        break;
                    case 'invalid-input-response':
                        $message .= 'The response parameter is invalid or malformed.'.PHP_EOL;
                        break;
                    case 'bad-request':
                        $message .= 'The request is invalid or malformed.'.PHP_EOL;
                        break;
                    case 'timeout-or-duplicate':
                        $message .= 'The response is no longer valid: either is too old or has been used previously.'.PHP_EOL;
                        break;
                }
            }

            throw new Exception($message);
        }

        return $json;
    }
}
