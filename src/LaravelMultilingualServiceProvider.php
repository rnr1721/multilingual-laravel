<?php

namespace rnr1721\MultilingualLaravel;

use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Contracts\Routing\Registrar;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\View\Compilers\BladeCompiler;
use rnr1721\MultilingualCore\Language;
use rnr1721\MultilingualCore\LanguageManager;
use rnr1721\MultilingualCore\Contracts\LanguageManagerInterface;
use rnr1721\MultilingualCore\Contracts\LocaleManagerInterface;
use rnr1721\MultilingualCore\Contracts\UrlGeneratorInterface;

/**
* Laravel Service Provider for Multilingual Support
*
* This service provider integrates the multilingual functionality into Laravel by:
* - Registering and publishing configuration
* - Setting up services (LanguageManager, UrlGenerator, etc.)
* - Registering middleware for language detection
* - Adding route macros for multilingual routes
* - Registering Blade directives
* - Setting up view composers for language data
*/
class LaravelMultilingualServiceProvider extends ServiceProvider
{
    /**
     * Register package services
     *
     * Sets up configuration and registers core services in the container
     */
    public function register(): void
    {
        $this->registerConfig();
        $this->registerServices();
    }

    /**
     * Bootstrap package services
     *
     * Sets up all runtime functionality like middleware, routes, and view components
     *
     * @param Registrar $router Laravel router instance
     * @param ViewFactory $view View factory instance
     * @param BladeCompiler $blade Blade compiler instance
     */
    public function boot(
        Registrar $router,
        ViewFactory $view,
        BladeCompiler $blade
    ): void {
        $this->publishConfig();
        $this->registerMiddleware($router);
        $this->registerRoutesMacro($router);
        $this->registerBladeDirectives($blade);
        $this->registerViewComposer($view);
    }

    /**
     * Register configuration file
     */
    private function registerConfig(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/multilingual.php', 'multilingual');
    }

    /**
     * Set up configuration publishing
     */
    private function publishConfig(): void
    {
        $this->publishes([
            __DIR__ . '/../config/multilingual.php' => config_path('multilingual.php'),
        ], 'config');
    }

    /**
     * Register core services in the container
     *
     * Sets up:
     * - LocaleManager for framework-specific locale handling
     * - LanguageManager for language state management
     * - UrlGenerator for language-aware URL generation
     * - Language detection middleware
     */
    private function registerServices(): void
    {

        $this->app->singleton(LocaleManagerInterface::class, function (Application $app) {
            return new LaravelLocaleManager($app);
        });

        $this->app->singleton(LanguageManagerInterface::class, function (Application $app) {
            return $this->createLanguageManager($app);
        });

        $this->app->singleton(UrlGeneratorInterface::class, function (Application $app) {
            return new LaravelUrlGenerator(
                $app->make(LanguageManagerInterface::class),
                $app['request'],
                $app['router'],
                $app['url'],
                $app['config']['multilingual']
            );
        });

        $this->app->singleton(LaravelLanguageMiddleware::class, function (Application $app) {
            return new LaravelLanguageMiddleware(
                $app->make(LanguageManagerInterface::class),
                $app->make(LaravelLanguageDetector::class)
            );
        });
    }

    /**
     * Create and configure the LanguageManager instance
     *
     * @param Application $app Laravel application instance
     * @return LanguageManager Configured language manager
     */
    private function createLanguageManager(Application $app): LanguageManager
    {
        $config = $app['config']['multilingual'];

        $languages = collect($config['languages'])
            ->map(fn (array $settings, string $code): Language => new Language(
                $code,
                $settings['name'],
                $settings['locale'],
                $settings['rtl'] ?? false
            ))
            ->all();

        return new LanguageManager(
            $app->make(LocaleManagerInterface::class),
            $languages,
            $config['default_language']
        );
    }

    /**
     * Register the language detection middleware
     *
     * @param Registrar $router Router instance
     */
    private function registerMiddleware(Registrar $router): void
    {
        $router->aliasMiddleware('language', LaravelLanguageMiddleware::class);
    }

    /**
     * Register the multilingual route macro
     *
     * This macro allows defining routes that will be automatically duplicated
     * for all configured languages with appropriate prefixes
     *
     * @param Registrar $router Router instance
     */
    private function registerRoutesMacro(Registrar $router): void
    {
        $manager = $this->app->make(LanguageManagerInterface::class);

        $router->macro('multilingual', function (callable $callback) use ($router, $manager) {
            // Execute callback for default language routes
            $callback($router);

            // Add prefixed routes for non-default languages
            foreach ($manager->getAvailableLanguages() as $language) {
                if ($language->getCode() !== $manager->getDefaultLanguage()->getCode()) {
                    $router->group([
                        'prefix' => $language->getCode(),
                        'middleware' => ['language']
                    ], $callback);
                }
            }
        });
    }

    /**
     * Register Blade directive for language-aware URL generation
     *
     * Adds @language directive for easy URL generation in templates
     *
     * @param BladeCompiler $blade Blade compiler instance
     */
    private function registerBladeDirectives(BladeCompiler $blade): void
    {
        $blade->directive('language', function (string $expression): string {
            return sprintf(
                '<?php echo app(%s)->generateUrl(%s); ?>',
                UrlGeneratorInterface::class,
                $expression
            );
        });
    }

    /**
     * Register view composer to inject language data
     *
     * Makes language information available in all views when enabled in config
     *
     * @param ViewFactory $view View factory instance
     */
    private function registerViewComposer(ViewFactory $view): void
    {
        if (!$this->app['config']['multilingual']['preload_page_links_in_templates']) {
            return;
        }

        $manager = $this->app->make(LanguageManagerInterface::class);
        $generator = $this->app->make(UrlGeneratorInterface::class);

        $view->composer('*', function ($view) use ($manager, $generator) {
            $view->with([
                'currentLanguage' => $manager->getCurrentLanguage(),
                'languages' => $manager->getAvailableLanguages(),
                'pageLinks' => $generator->getCurrentPageLanguages()
            ]);
        });
    }
}
