<?php

declare(strict_types=1);

namespace App\Handler;

use App\RecoveryCode;
use App\UserRepository;
use Geo6\Zend\Log\Log;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Mezzio\Authentication\UserInterface;
use Mezzio\Router\RouterInterface;
use Mezzio\Template\TemplateRendererInterface;
use Laminas\Log\Logger;
use Laminas\Mail\Message;
use Laminas\Mail\Transport\Smtp as SmtpTransport;
use Laminas\Mail\Transport\SmtpOptions;

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
                $url = 'http' . (isset($server['HTTPS']) && $server['HTTPS'] === 'on' ? 's' : '') . '://' . $server['HTTP_HOST'] . $redirect;

                self::sendEmail($this->config['mail'], $to, $user, $code->getCode(), $url);

                Log::write(
                    sprintf('data/log/%s.log', date('Ym')),
                    'Account recovery process initiated for e-mail address "{email}".',
                    ['email' => $to],
                    Logger::NOTICE
                );

                return new RedirectResponse($redirect . '?' . http_build_query(['email' => $to]));
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

    private static function sendEmail(array $config, string $to, UserInterface $user, string $code, string $url): void
    {
        $mail = new Message();
        $mail->setEncoding('UTF-8');
        $mail->setBody('Code: ' . $code . ' (' . (date('d.m.Y H:i', time() + RecoveryCode::TIMEOUT)) . ')' . PHP_EOL . $url);
        $mail->setFrom($config['from']);
        $mail->addTo($to, $user->getDetail('fullname'));
        $mail->setSubject('Account recovery - Verification code');

        $transport = new SmtpTransport();
        $transport->setOptions(new SmtpOptions($config['smtp']));
        $transport->send($mail);
    }
}
