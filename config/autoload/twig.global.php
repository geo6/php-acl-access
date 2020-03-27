<?php

declare(strict_types=1);

return [
    'dependencies' => [
        'aliases' => [
            Laminas\I18n\Translator\TranslatorInterface::class => Laminas\I18n\Translator\Translator::class,
        ],
        'factories'  => [
            App\Twig\TranslationExtension::class      => Laminas\ServiceManager\AbstractFactory\ReflectionBasedAbstractFactory::class,
            Laminas\I18n\Translator\Translator::class => App\Twig\TranslatorFactory::class,
        ],
    ],
    'templates' => [
        'paths' => [
            'app'      => ['templates/app'],
            'error'    => ['templates/error'],
            'includes' => ['templates/includes'],
            'layout'   => ['templates/layout'],
            'mail'     => ['templates/mail'],
        ],
    ],
    'twig' => [
        'extensions' => [
            App\Twig\TranslationExtension::class,
            Blast\BaseUrl\BasePathTwigExtension::class,
        ],
    ],
];
