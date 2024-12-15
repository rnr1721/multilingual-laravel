<?php

namespace rnr1721\MultilingualLaravel;

use rnr1721\MultilingualCore\Contracts\LanguageDetectorInterface;
use rnr1721\MultilingualCore\Contracts\languageManagerInterface;
use Illuminate\Http\Request;
use Closure;

/**
* Laravel Middleware for Language Detection and Setting
*
* This middleware handles language detection and setting for each HTTP request.
* It runs before the actual route handling and ensures that:
* - The correct language is detected from the URL
* - The application language is properly set
* - The framework locale is updated accordingly
*/
class LaravelLanguageMiddleware
{
    /**
     * Language manager instance for managing current language
     *
     * @var LanguageManagerInterface
     */
    private LanguageManagerInterface $languageManager;

    /**
     * Language detector for determining language from request
     *
     * @var LanguageDetectorInterface
     */
    private LanguageDetectorInterface $detector;

    /**
     * Initialize the middleware with required services
     *
     * @param LanguageManagerInterface $languageManager For managing language state
     * @param LanguageDetectorInterface $languageDetector For detecting language from request
     */
    public function __construct(
        LanguageManagerInterface $languageManager,
        LanguageDetectorInterface $languageDetector
    ) {
        $this->languageManager = $languageManager;
        $this->detector = $languageDetector;
    }

    /**
     * Handle the incoming request
     *
     * Process flow:
     * 1. Detect language from the request URL
     * 2. Set the detected language as current
     * 3. Framework locale is automatically updated via LanguageManager
     *
     * @param Request $request The incoming HTTP request
     * @param Closure $next The next middleware/handler in the chain
     * @return mixed Response from the next handler
     */
    public function handle(Request $request, Closure $next)
    {
        $language = $this->detector->detect();

        $this->languageManager->setCurrentLanguage($language);

        return $next($request);
    }
}
