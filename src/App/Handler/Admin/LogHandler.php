<?php

declare(strict_types=1);

namespace App\Handler\Admin;

use ArrayObject;
use ErrorException;
use Geo6\Laminas\Log\Log;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Permissions\Acl\AclInterface;
use Mezzio\Authentication\UserInterface;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class LogHandler implements RequestHandlerInterface
{
    const FNAME_REGEX = '/^([0-9]{4})([0-9]{2})\.log$/';
    const LOCAL_DIRECTORY = 'data/log/';

    /** @var string[] */
    private $external;

    /** @var TemplateRendererInterface */
    private $renderer;

    public function __construct(TemplateRendererInterface $renderer, array $logs = [])
    {
        $this->renderer = $renderer;
        $this->external = $logs;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $user = $request->getAttribute(UserInterface::class);
        $acl = $request->getAttribute(AclInterface::class);
        if ($acl->isAllowed($user->getIdentity(), 'admin.log') !== true) {
            return new HtmlResponse($this->renderer->render('error::403'), 403);
        }

        $id = $request->getAttribute('id');
        $year = $request->getAttribute('year');
        $month = $request->getAttribute('month');

        if (!is_null($id)) {
            $data = $this->getExternal(intval($id), $year, $month);
        } else {
            $data = $this->getLocal($year, $month);
        }

        $data = array_merge((array) $data, [
            'id'       => $id,
            'external' => $this->external,
        ]);

        return new HtmlResponse($this->renderer->render('app::admin/log', $data));
    }

    private function getExternal(int $id, ?string $year = null, ?string $month = null)
    {
        $keys = array_keys($this->external);
        $directory = isset($keys[$id]) ? $this->external[$keys[$id]] : null;

        if (is_null($directory) || !file_exists($directory) || !is_dir($directory) || !is_readable($directory)) {
            throw new ErrorException(sprintf('Invalid external log id #%d.', $id));
        }

        if (is_null($year) && is_null($month)) {
            $directory = rtrim($directory, '/');

            $logs = array_filter(
                glob($directory.'/*.log'),
                function (string $path) {
                    return preg_match(self::FNAME_REGEX, basename($path)) === 1;
                }
            );
            $last = end($logs);

            preg_match(self::FNAME_REGEX, basename($last), $matches);
            [, $year, $month] = $matches;
        }

        $path = self::getPath($directory, intval($year), $month);
        $log = file_exists($path) && is_readable($path) ? Log::read($path) : null;

        $previous = self::getPrevious(intval($year), intval($month));
        while (!file_exists(self::getPath($directory, $previous['year'], $previous['month']))) {
            $previous = self::getPrevious($previous['year'], intval($previous['month']));

            if ($previous['year'] < 2019) {
                $previous = null;
                break;
            }
        }

        $next = self::getNext(intval($year), intval($month));
        while (!file_exists(self::getPath($directory, $next['year'], $next['month']))) {
            $next = self::getNext($next['year'], intval($next['month']));

            if ($next['year'] > intval(date('Y'))) {
                $next = null;
                break;
            }
        }

        return new ArrayObject([
            'title'    => sprintf('%s : %s', $keys[$id], date('F Y', mktime(12, 0, 0, intval($month), 1, intval($year)))),
            'log'      => $log,
            'previous' => !is_null($previous) ? array_merge(['id' => $id], $previous) : null,
            'next'     => !is_null($next) ? array_merge(['id' => $id], $next) : null,
        ]);
    }

    private function getLocal(?string $year = null, ?string $month = null)
    {
        if (is_null($year) && is_null($month)) {
            $directory = rtrim(self::LOCAL_DIRECTORY, '/');

            $logs = glob($directory.'/*-login.log');
            $last = end($logs);

            preg_match('/^([0-9]{4})([0-9]{2})-login\.log$/', basename($last), $matches);
            [, $year, $month] = $matches;
        }

        $path = self::getPath(self::LOCAL_DIRECTORY, intval($year), $month, 'login');
        $logLogin = file_exists($path) && is_readable($path) ? Log::read($path) : null;

        $path = self::getPath(self::LOCAL_DIRECTORY, intval($year), $month, 'admin');
        $logAdmin = file_exists($path) && is_readable($path) ? Log::read($path) : null;

        $previous = self::getPrevious(intval($year), intval($month));
        while (!file_exists(self::getPath(self::LOCAL_DIRECTORY, $previous['year'], $previous['month'], 'login'))) {
            $previous = self::getPrevious($previous['year'], intval($previous['month']));

            if ($previous['year'] < 2019) {
                $previous = null;
                break;
            }
        }

        $next = self::getNext(intval($year), intval($month));
        while (!file_exists(self::getPath(self::LOCAL_DIRECTORY, $next['year'], $next['month'], 'login'))) {
            $next = self::getNext($next['year'], intval($next['month']));

            if ($next['year'] > intval(date('Y'))) {
                $next = null;
                break;
            }
        }

        return new ArrayObject([
            'title'    => date('F Y', mktime(12, 0, 0, intval($month), 1, intval($year))),
            'log'      => [
                'admin' => $logAdmin,
                'login' => $logLogin,
            ],
            'previous' => $previous,
            'next'     => $next,
        ]);
    }

    private static function getPrevious(int $year, int $month): array
    {
        if ($month === 1) {
            $month = 12;
            $year = $year - 1;
        } else {
            $month = $month - 1;
            $year = $year;
        }

        return ['year' => $year, 'month' => str_pad((string) $month, 2, '0', STR_PAD_LEFT)];
    }

    private static function getNext(int $year, int $month): array
    {
        if ($month === 12) {
            $month = 1;
            $year = $year + 1;
        } else {
            $month = $month + 1;
            $year = $year;
        }

        return ['year' => $year, 'month' => str_pad((string) $month, 2, '0', STR_PAD_LEFT)];
    }

    private static function getPath(string $directory, int $year, string $month, ?string $topic = null): string
    {
        $directory = rtrim($directory, '/');

        if (!is_null($topic)) {
            return sprintf('%s/%s%s-%s.log', $directory, $year, $month, $topic);
        }

        return sprintf('%s/%s%s.log', $directory, $year, $month);
    }
}
