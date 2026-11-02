<?php

namespace Tests\Feature;

use App\Helpers\ThemeContext;
use Tests\TestCase;

class ThemeExternalLinksTest extends TestCase
{
    public function testOnlyHttpLinksAreExposedInTheFrontendContext(): void
    {
        config([
            'custom.upwork_url' => 'javascript:alert(1)',
            'custom.developers_url' => 'javascript:alert(1)',
            'custom.support_url' => 'https://support.example.invalid/help',
        ]);

        $context = ThemeContext::make((object) ['taglines' => null, 'social_links' => null], []);

        $this->assertNull($context['upworkUrl']);
        $this->assertNull($context['developersUrl']);
        $this->assertSame('https://support.example.invalid/help', $context['supportUrl']);
    }

    public function testUnsetAndNonHttpUrlsAreHidden(): void
    {
        config([
            'custom.upwork_url' => null,
            'custom.support_url' => 'ftp://support.example.invalid/help',
        ]);

        $context = ThemeContext::make((object) ['taglines' => null, 'social_links' => null], []);

        $this->assertNull($context['upworkUrl']);
        $this->assertNull($context['supportUrl']);
    }

    public function testHttpUrlIsAllowed(): void
    {
        config([
            'custom.upwork_url' => 'http://profile.example.invalid/contact',
            'custom.developers_url' => 'https://docs.example.invalid',
            'custom.support_url' => null,
        ]);

        $context = ThemeContext::make((object) ['taglines' => null, 'social_links' => null], []);

        $this->assertSame('http://profile.example.invalid/contact', $context['upworkUrl']);
        $this->assertSame('https://docs.example.invalid', $context['developersUrl']);
        $this->assertNull($context['supportUrl']);
    }

    public function testCoffeeLinkDefaultsToBuyMeACoffeeAndCanBeHidden(): void
    {
        $about = (object) ['taglines' => null, 'social_links' => null];
        $this->assertSame('https://buymeacoffee.com/edilsonsantos', ThemeContext::make($about, [])['coffeeUrl']);

        config(['custom.coffee_url' => '']);
        $this->assertNull(ThemeContext::make($about, [])['coffeeUrl']);
    }
}
