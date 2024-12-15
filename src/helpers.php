<?php

use rnr1721\MultilingualCore\Contracts\UrlGeneratorInterface;
use rnr1721\MultilingualCore\Contracts\LanguageManagerInterface;

if (!function_exists('language_route')) {
    function language_route(?string $name = null, array $parameters = [], string $language = null): string
    {
        return app(UrlGeneratorInterface::class)->generateUrl($name, $parameters, $language);
    }
}

if (!function_exists('language_url')) {
    function language_url(?string $path = null, array $parameters = [], string $language = null): string
    {
        return app(UrlGeneratorInterface::class)->generateUrl($path, $parameters, $language);
    }
}

if (!function_exists('current_language')) {
    function current_language(): object
    {
        return app(LanguageManagerInterface::class)->getCurrentLanguage();
    }
}

if (!function_exists('available_languages')) {
    function available_languages(): array
    {
        return app(LanguageManagerInterface::class)->getAvailableLanguages();
    }
}

if (!function_exists('page_links')) {
    function page_links(): array
    {
        return app(UrlGeneratorInterface::class)->getCurrentPageLanguages();
    }
}
