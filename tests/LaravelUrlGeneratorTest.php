<?php

namespace rnr1721\MultilingualLaravel\Tests;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use rnr1721\MultilingualCore\Contracts\LanguageManagerInterface;
use rnr1721\MultilingualCore\Language;
use rnr1721\MultilingualLaravel\LaravelUrlGenerator;
use Illuminate\Routing\UrlGenerator;
use Illuminate\Routing\Router;
use Illuminate\Http\Request;

class LaravelUrlGeneratorTest extends TestCase
{
    private LaravelUrlGenerator $urlGenerator;
    private LanguageManagerInterface|MockObject $languageManager;
    private Request|MockObject $request;
    private Router|MockObject $router;
    private UrlGenerator|MockObject $laravelUrlGenerator;
    private array $config;
    private Language $defaultLanguage;
    private Language $ruLanguage;

    protected function setUp(): void
    {
        $this->languageManager = $this->createMock(LanguageManagerInterface::class);
        $this->request = $this->createMock(Request::class);
        $this->router = $this->createMock(Router::class);
        $this->laravelUrlGenerator = $this->createMock(UrlGenerator::class);

        $this->defaultLanguage = new Language('en', 'English', 'en_US');
        $this->ruLanguage = new Language('ru', 'Russian', 'ru_RU');

        $this->config = [
            'url' => ['exclude_default_language' => true]
        ];

        $this->languageManager
            ->expects($this->any())
            ->method('getDefaultLanguage')
            ->willReturn($this->defaultLanguage);

        $this->languageManager
            ->expects($this->any())
            ->method('getCurrentLanguage')
            ->willReturn($this->defaultLanguage);

        $this->languageManager
            ->expects($this->any())
            ->method('getAvailableLanguages')
            ->willReturn([$this->defaultLanguage, $this->ruLanguage]);

        $this->urlGenerator = new LaravelUrlGenerator(
            $this->languageManager,
            $this->request,
            $this->router,
            $this->laravelUrlGenerator,
            $this->config
        );
    }

    public function testGenerateUrlForRoot(): void
    {
        $this->request
            ->expects($this->any())
            ->method('path')
            ->willReturn('/');

        $this->laravelUrlGenerator
            ->expects($this->once())
            ->method('to')
            ->with('/')
            ->willReturn('http://example.com');

        $result = $this->urlGenerator->generateUrl();
        $this->assertEquals('http://example.com', $result);
    }

    public function testGenerateUrlForNonDefaultLanguage(): void
    {
        $this->request
            ->expects($this->any())
            ->method('path')
            ->willReturn('some/path');

        $this->laravelUrlGenerator
            ->expects($this->once())
            ->method('to')
            ->with('ru/some/path')
            ->willReturn('http://example.com/ru/some/path');

        $result = $this->urlGenerator->generateUrl(null, [], 'ru');
        $this->assertEquals('http://example.com/ru/some/path', $result);
    }

    public function testGenerateUrlForNamedRoute(): void
    {
        $this->request
            ->expects($this->any())
            ->method('path')
            ->willReturn('about');

        $this->router
            ->expects($this->once())
            ->method('has')
            ->with('about')
            ->willReturn(true);

        $this->laravelUrlGenerator
            ->expects($this->once())
            ->method('route')
            ->with('about', [], false)
            ->willReturn('/about');

        $this->laravelUrlGenerator
            ->expects($this->once())
            ->method('to')
            ->with('about')
            ->willReturn('http://example.com/about');

        $result = $this->urlGenerator->generateUrl('about');
        $this->assertEquals('http://example.com/about', $result);
    }

    public function testGenerateUrlWithParameters(): void
    {
        $this->request
            ->expects($this->any())
            ->method('path')
            ->willReturn('products');

        $this->laravelUrlGenerator
            ->expects($this->once())
            ->method('to')
            ->with('products')
            ->willReturn('http://example.com/products');

        $result = $this->urlGenerator->generateUrl(null, ['category' => 'books']);
        $this->assertEquals('http://example.com/products?category=books', $result);
    }

    public function testGetCurrentPageLanguages(): void
    {
        $this->request
            ->expects($this->any())
            ->method('query')
            ->willReturn(['param' => 'value']);

        $this->request
            ->expects($this->any())
            ->method('path')
            ->willReturn('test');

        $this->laravelUrlGenerator
            ->expects($this->any())
            ->method('to')
            ->willReturnCallback(function ($path) {
                return 'http://example.com/' . ltrim($path, '/');
            });

        $result = $this->urlGenerator->getCurrentPageLanguages();

        $this->assertArrayHasKey('en', $result);
        $this->assertArrayHasKey('ru', $result);

        $this->assertEquals('English', $result['en']['name']);
        $this->assertEquals('Russian', $result['ru']['name']);

        $this->assertTrue($result['en']['active']);
        $this->assertFalse($result['ru']['active']);

        $this->assertTrue(
            str_contains($result['ru']['url'], 'http://example.com/ru/test')
        );
    }

    public function testGenerateUrlWithLanguagePrefixCleaning(): void
    {
        $this->request
            ->expects($this->any())
            ->method('path')
            ->willReturn('ru/page');

        $this->laravelUrlGenerator
            ->expects($this->once())
            ->method('to')
            ->with('ru/page')
            ->willReturn('http://example.com/ru/page');

        $result = $this->urlGenerator->generateUrl(null, [], 'ru');
        $this->assertEquals('http://example.com/ru/page', $result);
    }
}
