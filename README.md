### Laravel Multilingual Support Package (Languge switcher)

This package provides multilingual support for Laravel applications, allowing easy management of multiple languages, automatic URL prefixing, language switching and locale handling.

Looking for a robust multilingual solution for your Laravel project? This package provides a clean, efficient way to handle multiple languages in your web applications with a focus on developer experience and SEO-friendly URLs.

What makes it special? Unlike traditional approaches, it separates core multilingual logic from framework implementation, making it more maintainable and portable. You get automatic language detection from URLs, smart prefix handling, and seamless locale switching - all while maintaining Laravel's elegant syntax and routing flexibility.

The package is production-ready with features like RTL support, automatic SEO-friendly URL generation, and comprehensive Blade integration. It's thoroughly tested and follows Laravel best practices, making it a reliable choice for projects of any size - from small corporate websites to large international platforms.

Integration is a breeze: just install the package, define your languages in config, and use the intuitive multilingual() route macro. The package handles all the complexities of language switching and URL generation while keeping your code clean and maintainable. No database queries, minimal overhead, and maximum flexibility - just the way Laravel developers like it.

## Requirements:

- PHP 8.1 or higher
- Laravel 10.0 or higher
- rnr1721/multilingual-core: ^1.0 (installed automatically as requirement)

## Installation in project

```shell
composer require rnr1721/multilingual-laravel
```

## Features:

- Automatic language detection from URL
- Language prefix management in URLs
- Blade directives for language switching
- Route macro for multilingual routes
- Automatic framework locale switching
- View variables for language data
- RTL language support

Middleware: No need to manually manage language detection; LaravelLanguageMiddleware automatically switches the current language based on the URL.

## Configuration:

Publish the configuration file:

```shell
php artisan vendor:publish --provider="rnr1721\MultilingualLaravel\LaravelMultilingualServiceProvider" --tag="config"
```

Configure your languages in config/multilingual.php:

```php
<?php

return [
    'languages' => [
        'en' => [
            'name' => 'English',
            'locale' => 'en_US',
            'rtl' => false
        ],
        'es' => [
            'name' => 'Español',
            'locale' => 'es_ES',
            'rtl' => false
        ]
    ],
    'default_language' => 'en',
    'url' => [
        'exclude_default_language' => true
    ],
    'preload_page_links_in_templates' => true
];
```

## Usage:

1. Define multilingual routes:

```php
Route::multilingual(function () {
    Route::get('/', function () {
        return view('welcome');
    })->name('welcome');
    
    Route::get('/about', function () {
        return view('about');
    })->name('about');
});
```

This will create routes for all configured languages:

- / and /about for English (default)
- /es and /es/about for Spanish

2. Use in templates:

```blade
<nav>
    <ul>
        @foreach($pageLinks as $pageLink)
            <li>
                <a href="{{ $pageLink['url'] }}"
                   @if($pageLink['active']) class="active" @endif>
                    {{ $pageLink['name'] }}
                </a>
            </li>
        @endforeach
    </ul>
</nav>
```

3. Generate language-specific URLs:

```blade
@language('route.name')
// or
@language('route.name', ['param' => 'value'])
// or
@language('route.name', [], 'es')
```

4. Available helper functions:

- language_route($name, $parameters = [], $language = null)
- language_url($path, $parameters = [], $language = null)
- current_language()
- available_languages()
- page_links()

5. Available view variables:

- $currentLanguage - Current language object
- $languages - Array of all available languages
- $pageLinks - Array of language URLs for current page

6. Translations:

Place your translation files in resources/lang/{locale}/ directories matching the locales in your config:

resources/
└── lang/
    ├── en_US/
    │   └── messages.php
    └── es_ES/
        └── messages.php

Use translations in templates:

```blade
{{ __('messages.welcome') }}
```

## Testing:

```shell
composer test
```

## Code Style:

```shell
composer cs-check
composer cs-fix
```

## Static Analysis:

```shell
composer phpstan
```

## License:
MIT

## Credits:

Eugeny G rnr1721@gmail.com

For more detailed documentation and examples, please visit the GitHub repository.
