<?php

namespace rnr1721\MultilingualLaravel;

use Illuminate\Contracts\Foundation\Application;
use rnr1721\MultilingualCore\Contracts\LocaleManagerInterface;

/**
* Laravel implementation of the LocaleManager
*
* This class handles Laravel-specific locale switching functionality.
* It provides a clean interface between the framework-agnostic core
* and Laravel's locale management system.
*/
class LaravelLocaleManager implements LocaleManagerInterface
{
    /**
    * Laravel application instance
    *
    * @var Application
    */
    private Application $app;

    /**
     * Initialize the locale manager
     *
     * @param Application $app Laravel application instance
     */
    public function __construct(
        Application $app
    ) {
        $this->app = $app;
    }

    /**
     * Set the application locale
     *
     * Updates Laravel's active locale, affecting:
     * - Translation selection
     * - Date/time formatting
     * - Number formatting
     * - Other locale-aware features
     *
     * @param string $locale The locale to set (e.g., 'en_US', 'ru_RU')
     * @return void
     */
    public function setLocale(string $locale): void
    {
        $this->app->setLocale($locale);
    }
}
