<?php

namespace App\Twig;

use Laminas\I18n\Translator\TranslatorInterface;
use Psr\Container\ContainerInterface;

class TranslationExtensionFactory
{
    public function __invoke(ContainerInterface $container): TranslationExtension
    {
        $translator = $container->get(TranslatorInterface::class);

        return new TranslationExtension($translator);
    }
}
