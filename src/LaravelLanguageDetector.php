<?php

namespace rnr1721\MultilingualLaravel;

use rnr1721\MultilingualCore\Contracts\LanguageDetectorInterface;
use rnr1721\MultilingualCore\Contracts\LanguageManagerInterface;
use Illuminate\Http\Request;

/**
 * Class LaravelLanguageDetector
 *
 * Detects the current language based on the URL or defaults.
 * Implements the LanguageDetectorInterface for compatibility with the multilingual core system.
 */
class LaravelLanguageDetector implements LanguageDetectorInterface
{
    /**
     * Language manager instance.
     *
     * Responsible for managing the available languages and retrieving default language information.
     *
     * @var LanguageManagerInterface
     */

    protected LanguageManagerInterface $languageManager;
    /**
     * The HTTP request instance.
     *
     * Provides access to the current request for analyzing the URL structure.
     *
     * @var Request
     */
    protected Request $request;

    /**
     * Initialize the language detector.
     *
     * @param LanguageManagerInterface $languageManager The language manager instance.
     * @param Request $request The current HTTP request instance.
     */
    public function __construct(
        LanguageManagerInterface $languageManager,
        Request $request
    ) {
        $this->languageManager = $languageManager;
        $this->request = $request;
    }

    /**
     * Detects the current language based on the request.
     *
     * - If the root route ("/" or "") is requested, returns the default language.
     * - If the first segment of the URL matches a valid language, returns that language.
     * - Otherwise, returns the default language.
     *
     * @return string The detected language code.
     */
    public function detect(): string
    {
        // For root route
        if ($this->request->path() === '/' || $this->request->path() === '') {
            return $this->languageManager->getDefaultLanguage()->getCode();
        }

        // Detect from URL
        $segment = $this->request->segment(1);
        if ($segment && $this->languageManager->hasLanguage($segment)) {
            return $segment;
        }

        // For another URLs we will return default language
        return $this->languageManager->getDefaultLanguage()->getCode();
    }
}
