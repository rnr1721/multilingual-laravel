<?php

namespace rnr1721\MultilingualLaravel;

use rnr1721\MultilingualCore\Contracts\LanguageManagerInterface;
use rnr1721\MultilingualCore\Contracts\UrlGeneratorInterface;
use Illuminate\Routing\UrlGenerator;
use Illuminate\Routing\Router;
use Illuminate\Http\Request;

/**
* Laravel implementation of URL generation for multilingual routes
*
* This class handles the generation of language-specific URLs for Laravel applications.
* It manages language prefixes, default language exclusion, and route parameters.
*/
class LaravelUrlGenerator implements UrlGeneratorInterface
{
    /**
     * @var LanguageManagerInterface Language manager instance
     */
    private LanguageManagerInterface $languageManager;

    /**
     * @var Request Current HTTP request
     */
    private Request $request;

    /**
     * @var Router Laravel router instance
     */
    private Router $router;

    /**
     * @var UrlGenerator Laravel URL generator instance
     */
    private UrlGenerator $urlGenerator;

    /**
     * @var array Package configuration
     */
    private array $config;

    /**
     * Initialize URL generator with required dependencies
     *
     * @param LanguageManagerInterface $languageManager Language manager instance
     * @param Request $request Current HTTP request
     * @param Router $router Laravel router instance
     * @param UrlGenerator $urlGenerator Laravel URL generator
     * @param array $config Package configuration
     */
    public function __construct(
        LanguageManagerInterface $languageManager,
        Request $request,
        Router $router,
        UrlGenerator $urlGenerator,
        array $config
    ) {
        $this->languageManager = $languageManager;
        $this->request = $request;
        $this->router = $router;
        $this->urlGenerator = $urlGenerator;
        $this->config = $config;
    }

    /**
     * Generate URL with proper language prefix
     *
     * This method handles various URL generation scenarios:
     * - Root path handling
     * - Default language exclusion from URL
     * - Named route resolution
     * - Query parameter handling
     * - Language prefix management
     *
     * @param string|null $route Route name or path (null for current path)
     * @param array $parameters Query parameters to append to URL
     * @param string|null $languageCode Specific language code (null for current language)
     * @return string Generated URL with appropriate language prefix
     */
    public function generateUrl(string $route = null, array $parameters = [], string $languageCode = null): string
    {
        // Get current language
        $languageCode = $languageCode ?? $this->languageManager->getCurrentLanguage()->getCode();
        $isDefaultLanguage = $languageCode === $this->languageManager->getDefaultLanguage()->getCode();

        $currentPath = $this->request->path();

        // Check if current path is language code
        $isLanguageOnlyPath = in_array($currentPath, array_map(
            fn ($lang) => $lang->getCode(),
            $this->languageManager->getAvailableLanguages()
        ));

        // Create base URL
        if ($isLanguageOnlyPath || $currentPath === '/' || $currentPath === '') {
            if ($isDefaultLanguage) {
                $baseUrl = $this->urlGenerator->to('/');
            } else {
                $baseUrl = $this->urlGenerator->to('/' . $languageCode);
            }
        } else {
            if ($route === null) {
                $url = $currentPath;
            } elseif ($this->router->has($route)) {
                $url = $this->urlGenerator->route($route, $parameters, false);
            } else {
                $url = $route;
            }

            $url = trim($url, '/');

            // Clear language prefixes
            foreach ($this->languageManager->getAvailableLanguages() as $lang) {
                if (str_starts_with($url, $lang->getCode() . '/')) {
                    $url = substr($url, strlen($lang->getCode()) + 1);
                    break;
                }
            }

            // Create base URL
            if ($isDefaultLanguage && ($this->config['url']['exclude_default_language'] ?? true)) {
                $baseUrl = $this->urlGenerator->to($url === '' ? '/' : $url);
            } else {
                $baseUrl = $this->urlGenerator->to($url === '' ? $languageCode : $languageCode . '/' . $url);
            }
        }

        if (!empty($parameters)) {
            return $baseUrl . '?' . http_build_query($parameters);
        }

        return $baseUrl;
    }

    /**
     * Get language variations of current page URL
     *
     * Generates a map of all available languages with their:
     * - URLs for current page
     * - Active status
     * - Language name
     * - Language code
     * - Locale
     *
     * @return array<string, array{
     *     url: string,
     *     active: bool,
     *     name: string,
     *     code: string,
     *     locale: string
     * }> Array of language data indexed by language code
     */
    public function getCurrentPageLanguages(): array
    {
        $currentLanguage = $this->languageManager->getCurrentLanguage();
        $languages = $this->languageManager->getAvailableLanguages();
        $result = [];
        foreach ($languages as $language) {
            $currentQuery = $this->request->query();
            $code = $language->getCode();
            $result[$code] = [
                'url' => $this->generateUrl(null, $currentQuery, $code),
                'active' => $currentLanguage->getCode() === $language->getCode(),
                'name' => $language->getName(),
                'code' => $language->getCode(),
                'locale' => $language->getLocale()
            ];
        }
        return $result;
    }
}
