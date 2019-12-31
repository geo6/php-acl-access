<?php

declare(strict_types=1);

return [
    'dependencies' => [
        'aliases' => [
            Zend\I18n\Translator\TranslatorInterface::class => Zend\I18n\Translator\Translator::class,
        ],
        'factories'  => [
            App\Twig\TranslationExtension::class   => Zend\ServiceManager\AbstractFactory\ReflectionBasedAbstractFactory::class,
            Zend\I18n\Translator\Translator::class => App\Twig\TranslatorFactory::class,
        ],
    ],
    'twig' => [
        'extensions' => [
            App\Twig\TranslationExtension::class,
            Blast\BaseUrl\BasePathTwigExtension::class,
        ],
    ],
];
