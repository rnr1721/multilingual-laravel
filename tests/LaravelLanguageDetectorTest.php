<?php

namespace rnr1721\MultilingualLaravel\Tests;

use PHPUnit\Framework\TestCase;
use Illuminate\Http\Request;
use rnr1721\MultilingualCore\Contracts\LanguageManagerInterface;
use rnr1721\MultilingualCore\Language;
use rnr1721\MultilingualLaravel\LaravelLanguageDetector;
use PHPUnit\Framework\MockObject\MockObject;

class LaravelLanguageDetectorTest extends TestCase
{
    private Request|MockObject $request;
    private LanguageManagerInterface|MockObject $languageManager;
    private LaravelLanguageDetector $detector;
    private Language $defaultLanguage;

    protected function setUp(): void
    {
        $this->request = $this->createMock(Request::class);
        $this->languageManager = $this->createMock(LanguageManagerInterface::class);

        $this->defaultLanguage = new Language('en', 'English', 'en_US');

        $this->languageManager
            ->expects($this->any())
            ->method('getDefaultLanguage')
            ->willReturn($this->defaultLanguage);

        $this->detector = new LaravelLanguageDetector(
            $this->languageManager,
            $this->request
        );
    }

    public function testDetectRootPath(): void
    {
        $this->request
            ->expects($this->any())
            ->method('path')
            ->willReturn('/');

        $this->assertEquals('en', $this->detector->detect());
    }

    public function testDetectLanguageFromSegment(): void
    {
        $this->request
            ->expects($this->any())
            ->method('path')
            ->willReturn('ru/test');

        $this->request
            ->expects($this->once())
            ->method('segment')
            ->with(1)
            ->willReturn('ru');

        $this->languageManager
            ->expects($this->once())
            ->method('hasLanguage')
            ->with('ru')
            ->willReturn(true);

        $this->assertEquals('ru', $this->detector->detect());
    }

    public function testFallbackToDefaultLanguage(): void
    {
        $this->request
            ->expects($this->any())
            ->method('path')
            ->willReturn('invalid/test');

        $this->request
            ->expects($this->once())
            ->method('segment')
            ->with(1)
            ->willReturn('invalid');

        $this->languageManager
            ->expects($this->once())
            ->method('hasLanguage')
            ->with('invalid')
            ->willReturn(false);

        $this->assertEquals('en', $this->detector->detect());
    }
}
