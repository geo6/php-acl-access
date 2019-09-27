<?php

declare(strict_types=1);

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zend\Expressive\Template\TemplateRendererInterface;

class TemplateMiddleware implements MiddlewareInterface
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

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $lang = $request->getAttribute(LanguageMiddleware::LANGUAGE_ATTRIBUTE);

        $this->renderer->addDefaultParam($this->renderer::TEMPLATE_ALL, 'lang', $lang);

        $this->renderer->addDefaultParam(
            $this->renderer::TEMPLATE_ALL,
            'global',
            [
                'logo'         => $this->config['logo'],
                'title'        => $this->config['title'],
                'attributions' => $this->config['attributions'],
            ]
        );

        return $handler->handle($request);
    }
}
