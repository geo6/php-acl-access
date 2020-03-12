<?php

declare(strict_types=1);

namespace App\Twig;

use Psr\Container\ContainerInterface;
use Laminas\I18n\Translator\Translator;

class TranslatorFactory
{
    const DIRECTORY = 'data/languages';

    public function __invoke(ContainerInterface $container): Translator
    {
        $translator = new Translator();

        // $translator->addTranslationFilePattern('gettext', self::DIRECTORY, '%s/default.mo');
        $translator->addTranslationFilePattern('phparray', self::DIRECTORY, '%s/default.php');
        $translator->addTranslationFilePattern('ini', self::DIRECTORY, '%s/default.ini');

        // $translator->setFallbackLocale('en');

        return $translator;
    }
}
