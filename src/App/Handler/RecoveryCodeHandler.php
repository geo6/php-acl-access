<?php

declare(strict_types=1);

namespace App\Handler;

use App\Mail;
use App\RecoveryCode;
use App\UserRepository;
use Geo6\Laminas\Log\Log;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Log\Logger;
use Mezzio\Template\TemplateRendererInterface;
use PDO;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RandomLib;
use SecurityLib\Strength;

class RecoveryCodeHandler implements RequestHandlerInterface
{
    /** @var array */
    private $config;

    /** @var TemplateRendererInterface */
    private $renderer;

    public function __construct(TemplateRendererInterface $renderer, array $config)
    {
        $this->renderer = $renderer;
        $this->config = $config;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $query = $request->getQueryParams();
        $uuid = $request->getAttribute('uuid');

        RecoveryCode::clean();

        if ('POST' === $request->getMethod()) {
            $params = $request->getParsedBody();

            $email = $params['email'] ?? null;
            $code = $params['code'] ?? null;

            $path = RecoveryCode::DIRECTORY.'/'.$uuid;

            if (file_exists($path) && is_readable($path)) {
                $content = parse_ini_file($path);

                if ($content['code'] === $code && $content['email'] === $email) {
                    unlink($path);

                    $userRepository = new UserRepository($this->config['authentication']['pdo']);
                    $user = $userRepository->search($this->config['authentication']['pdo']['field']['email'], $content['email']);

                    if (!is_null($user)) {
                        $password = self::resetPassword($this->config['authentication']['pdo'], $content['email']);

                        $to = (string) $user->getDetail($this->config['authentication']['pdo']['field']['email']);

                        Mail::send(
                            $this->config['mail'],
                            $this->renderer,
                            $to,
                            'Account recovery - New password',
                            '@mail/password/reset.html.twig',
                            [
                                'fullname' => $user->getDetail('fullname'),
                                'password' => $password,
                            ]
                        );

                        Log::write(
                            sprintf('data/log/%s-login.log', date('Ym')),
                            'Password reset for "{identity}" ({email}).',
                            ['identity' => $user->getIdentity(), 'email' => $to],
                            Logger::NOTICE,
                            $request
                        );

                        $success = true;
                    }
                }
            }

            $error = true;
        }

        return new HtmlResponse($this->renderer->render(
            'app::recovery-code',
            [
                'uuid'    => $uuid,
                'email'   => $query['email'] ?? null,
                'success' => $success ?? false,
                'error'   => $error ?? false,
            ]
        ));
    }

    private static function resetPassword(array $config, string $email): string
    {
        $factory = new RandomLib\Factory();
        $generator = $factory->getGenerator(new Strength(Strength::MEDIUM));
        $password = $generator->generateString(8, '0123456789abcdefghijklmnopqrstuvwxyz');

        $hash = password_hash($password, PASSWORD_DEFAULT);

        $pdo = new PDO(
            $config['dsn'],
            $config['username'] ?? null,
            $config['password'] ?? null
        );

        $sql = sprintf(
            'UPDATE %s SET %s = :hash WHERE %s = :email',
            $config['table'],
            $config['field']['password'],
            $config['field']['email']
        );
        $stmt = $pdo->prepare($sql);

        // if (false === $stmt) { }

        $stmt->bindParam(':hash', $hash);
        $stmt->bindParam(':email', $email);

        $stmt->execute();

        return $password;
    }
}
