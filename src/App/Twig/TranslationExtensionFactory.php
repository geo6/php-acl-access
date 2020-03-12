<?php

namespace App\Twig;

use Psr\Container\ContainerInterface;
use Laminas\I18n\Translator\TranslatorInterface;

class TranslationExtensionFactory
{
    public function __invoke(ContainerInterface $container): TranslationExtension
    {
        $translator = $container->get(TranslatorInterface::class);

        return new TranslationExtension($translator);
    }
}
