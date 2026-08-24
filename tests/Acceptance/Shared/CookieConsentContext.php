<?php

declare(strict_types=1);

namespace App\Tests\Acceptance\Shared;

use App\Shared\Infrastructure\UI\Web\CookieConsent\CookieConsentDecision;
use Behat\Behat\Context\Context;
use Behat\Step\Then;
use Behat\Step\When;
use PHPUnit\Framework\Assert;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

final class CookieConsentContext implements Context
{
    private const string PATH = '/es/cookie-consent';

    private readonly AcceptanceClient $client;

    public function __construct(KernelBrowser $client)
    {
        $this->client = AcceptanceClient::instance($client);
    }

    #[When('I send the cookie consent decision :decision')]
    public function iSendTheCookieConsentDecision(string $decision): void
    {
        $this->post($decision, $this->csrfTokenOfTheBanner(), []);
    }

    #[When('I send the cookie consent decision :decision from :referer')]
    public function iSendTheCookieConsentDecisionFrom(string $decision, string $referer): void
    {
        $this->post($decision, $this->csrfTokenOfTheBanner(), ['HTTP_REFERER' => $referer]);
    }

    #[When('I send the cookie consent decision :decision with an invalid CSRF token')]
    public function iSendTheCookieConsentDecisionWithAnInvalidCsrfToken(string $decision): void
    {
        $this->post($decision, 'not-a-valid-token', []);
    }

    #[Then('the Google fonts of the page are gated behind the consent')]
    public function theGoogleFontsOfThePageAreGatedBehindTheConsent(): void
    {
        Assert::assertMatchesRegularExpression(
            '#<template data-consent-gated>.*fonts\.googleapis\.com/css2.*</template>#s',
            $this->pageContent(),
        );
    }

    #[Then('the Google fonts of the page load directly')]
    public function theGoogleFontsOfThePageLoadDirectly(): void
    {
        $content = $this->pageContent();

        Assert::assertStringContainsString('fonts.googleapis.com/css2', $content);
        Assert::assertStringNotContainsString('<template data-consent-gated>', $content);
    }

    #[Then('the cookie consent redirects to :location')]
    public function theCookieConsentRedirectsTo(string $location): void
    {
        $response = $this->client->getResponse();

        Assert::assertTrue($response->isRedirect());
        Assert::assertSame($location, $response->headers->get('Location'));
    }

    #[Then('the cookie consent value should be :value')]
    public function theCookieConsentValueShouldBe(string $value): void
    {
        $cookie = $this->client->getCookieJar()->get(CookieConsentDecision::COOKIE_NAME);

        Assert::assertNotNull($cookie);
        Assert::assertSame($value, $cookie->getValue());
    }

    #[Then('there should be no cookie consent value')]
    public function thereShouldBeNoCookieConsentValue(): void
    {
        Assert::assertNull($this->client->getCookieJar()->get(CookieConsentDecision::COOKIE_NAME));
    }

    private function pageContent(): string
    {
        $content = $this->client->getResponse()->getContent();

        Assert::assertIsString($content);

        return $content;
    }

    /** @param array<string, string> $serverParameters */
    private function post(string $decision, string $token, array $serverParameters): void
    {
        $this->client->requestWithFiles(
            'POST',
            self::PATH,
            ['decision' => $decision, '_token' => $token],
            [],
            $serverParameters,
        );
    }

    private function csrfTokenOfTheBanner(): string
    {
        $token = $this->client->getCrawler()->filter('.cookie-consent input[name="_token"]')->first();

        Assert::assertGreaterThan(0, $token->count(), 'El banner de cookies no está en la página');

        return $token->attr('value') ?? '';
    }
}
