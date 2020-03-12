<?php

declare(strict_types=1);

return [
    'dependencies' => [
        'aliases' => [
            Laminas\I18n\Translator\TranslatorInterface::class => Laminas\I18n\Translator\Translator::class,
        ],
        'factories'  => [
            App\Twig\TranslationExtension::class   => Laminas\ServiceManager\AbstractFactory\ReflectionBasedAbstractFactory::class,
            Laminas\I18n\Translator\Translator::class => App\Twig\TranslatorFactory::class,
        ],
    ],
    'twig' => [
        'extensions' => [
            App\Twig\TranslationExtension::class,
        ],
    ],
];
