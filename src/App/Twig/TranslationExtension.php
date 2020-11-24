<?php

declare(strict_types=1);

namespace App\Twig;

use Laminas\I18n\Translator\Translator;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class TranslationExtension extends AbstractExtension
{
    /** @var Translator */
    private $translator;

    public function __construct(Translator $translator)
    {
        $this->translator = $translator;
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('trans', [$this, 'translate'], ['needs_context' => true]),
        ];
    }

    public function translate(array $context, string $message, string $textDomain = 'default', ?int $count = null): string
    {
        if (!is_null($count) && $count > 1) {
            return $this->translator->translatePlural($message, '', $count, $textDomain, $context['lang'] ?? 'en');
        } else {
            return $this->translator->translate($message, $textDomain, $context['lang'] ?? 'en');
        }
    }
}
