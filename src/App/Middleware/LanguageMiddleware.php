<?php

declare(strict_types=1);

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class LanguageMiddleware implements MiddlewareInterface
{
    public const LANGUAGE_ATTRIBUTE = 'language';

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $cookie = $request->getCookieParams();
        $query = $request->getQueryParams();

        $acceptLanguage = explode(',', $request->getHeaderLine('Accept-Language'));
        if (isset($acceptLanguage[0])) {
            $acceptLanguage[0] = trim($acceptLanguage[0]);

            if (preg_match('/^([a-z]{2,3})(?:-.+)?(?:;q=.+)?$/i', $acceptLanguage[0], $matches) === 1) {
                $accept = $matches[1];
            }
        }

        $lang = $query['lang'] ?? $cookie['lang'] ?? $accept ?? 'en';

        setcookie('lang', $lang);

        return $handler->handle($request->withAttribute(self::LANGUAGE_ATTRIBUTE, $lang));
    }
}
