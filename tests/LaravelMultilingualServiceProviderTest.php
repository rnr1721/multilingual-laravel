<?php

namespace rnr1721\MultilingualLaravel\Tests;

use Orchestra\Testbench\TestCase;
use rnr1721\MultilingualCore\Contracts\LanguageManagerInterface;
use rnr1721\MultilingualCore\Contracts\LocaleManagerInterface;
use rnr1721\MultilingualCore\Contracts\UrlGeneratorInterface;
use rnr1721\MultilingualLaravel\LaravelLanguageMiddleware;
use rnr1721\MultilingualLaravel\LaravelMultilingualServiceProvider;
use Illuminate\Support\Facades\View;

class LaravelMultilingualServiceProviderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (!is_dir(__DIR__ . '/stubs')) {
            mkdir(__DIR__ . '/stubs');
        }
    }

    protected function tearDown(): void
    {
        if (file_exists(__DIR__ . '/stubs/test.blade.php')) {
            unlink(__DIR__ . '/stubs/test.blade.php');
        }
        if (is_dir(__DIR__ . '/stubs')) {
            rmdir(__DIR__ . '/stubs');
        }

        parent::tearDown();
    }

    protected function getPackageProviders($app): array
    {
        return [LaravelMultilingualServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('multilingual', [
            'languages' => [
                'en' => [
                    'name' => 'English',
                    'locale' => 'en_US',
                    'rtl' => false
                ],
                'ru' => [
                    'name' => 'Russian',
                    'locale' => 'ru_RU',
                    'rtl' => false
                ]
            ],
            'default_language' => 'en',
            'preload_page_links_in_templates' => true,
            'url' => [
                'exclude_default_language' => true
            ]
        ]);
    }

    public function testServiceRegistration(): void
    {
        $this->assertTrue($this->app->bound(LocaleManagerInterface::class));
        $this->assertTrue($this->app->bound(LanguageManagerInterface::class));
        $this->assertTrue($this->app->bound(UrlGeneratorInterface::class));
        $this->assertTrue($this->app->bound(LaravelLanguageMiddleware::class));
    }

    public function testLanguageManagerConfiguration(): void
    {
        $manager = $this->app->make(LanguageManagerInterface::class);

        $this->assertEquals('en', $manager->getDefaultLanguage()->getCode());
        $this->assertCount(2, $manager->getAvailableLanguages());
        $this->assertTrue($manager->hasLanguage('ru'));
    }

    public function testMiddlewareRegistration(): void
    {
        $router = $this->app['router'];
        $middlewareGroups = $router->getMiddlewareGroups();

        $this->assertArrayHasKey('language', $router->getMiddleware());
    }

    public function testBladeDirectiveRegistration(): void
    {
        $blade = $this->app['view']->getEngineResolver()
                     ->resolve('blade')->getCompiler();

        $compiled = $blade->compileString('@language("test")');
        $this->assertStringContainsString('generateUrl', $compiled);
    }

    public function testViewComposerRegistration(): void
    {
        View::addLocation(__DIR__ . '/stubs');

        $viewContent = '{{ $currentLanguage->getCode() }}';
        $this->createTestView($viewContent);

        $content = view('test')->render();
        $this->assertEquals('en', trim($content));
    }

    public function testConfigPublishing(): void
    {
        $this->artisan('vendor:publish', [
            '--provider' => LaravelMultilingualServiceProvider::class,
            '--tag' => 'config'
        ]);

        $this->assertTrue(file_exists(config_path('multilingual.php')));

        if (file_exists(config_path('multilingual.php'))) {
            unlink(config_path('multilingual.php'));
        }
    }

    private function createTestView(string $content): void
    {
        if (!is_dir(__DIR__ . '/stubs')) {
            mkdir(__DIR__ . '/stubs');
        }
        file_put_contents(__DIR__ . '/stubs/test.blade.php', $content);
    }
}
