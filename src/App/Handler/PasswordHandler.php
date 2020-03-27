<?php

declare(strict_types=1);

namespace App\Handler;

use App\Mail;
use App\RecoveryCode;
use App\UserRepository;
use Geo6\Laminas\Log\Log;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Log\Logger;
use Mezzio\Router\RouterInterface;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class PasswordHandler implements RequestHandlerInterface
{
    /** @var array */
    private $config;

    /** @var TemplateRendererInterface */
    private $renderer;

    /** @var RouterInterface */
    private $router;

    public function __construct(TemplateRendererInterface $renderer, RouterInterface $router, array $config)
    {
        $this->renderer = $renderer;
        $this->router = $router;
        $this->config = $config;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        RecoveryCode::clean();

        if ('POST' === $request->getMethod()) {
            $server = $request->getServerParams();
            $params = $request->getParsedBody();

            $userRepository = new UserRepository($this->config['authentication']['pdo']);
            $user = $userRepository->search($params['email']);

            if (!is_null($user)) {
                $to = (string) $user->getDetail($this->config['authentication']['pdo']['field']['email']);

                $code = new RecoveryCode();
                $uuid = $code->store(['email' => $to]);

                $redirect = $this->router->generateUri('password.code', ['uuid' => $uuid]);
                $url = 'http'.(isset($server['HTTPS']) && $server['HTTPS'] === 'on' ? 's' : '').'://'.$server['HTTP_HOST'].$redirect;

                Mail::send(
                    $this->config['mail'],
                    $this->renderer,
                    $to,
                    'Account recovery - Verification code',
                    '@mail/password/code.html.twig',
                    [
                        'fullname' => $user->getDetail('fullname'),
                        'code'     => $code->getCode(),
                        'timeout'  => (date('d.m.Y H:i', time() + RecoveryCode::TIMEOUT)),
                        'url'      => $url,
                    ]
                );

                Log::write(
                    sprintf('data/log/%s-login.log', date('Ym')),
                    'Account recovery process initiated for e-mail address "{email}".',
                    ['email' => $to],
                    Logger::NOTICE,
                    $request
                );

                return new RedirectResponse($redirect.'?'.http_build_query(['email' => $to]));
            } else {
                $error = true;
            }
        }

        return new HtmlResponse($this->renderer->render(
            'app::password',
            [
                'error' => $error ?? false,
            ]
        ));
    }
}
