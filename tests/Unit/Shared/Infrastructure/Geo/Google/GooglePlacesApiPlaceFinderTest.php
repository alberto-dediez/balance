<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Geo\Google;

use App\Shared\Domain\Model\Geo\PlaceKind;
use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Infrastructure\Geo\Google\GooglePlacesApiPlaceFinder;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class GooglePlacesApiPlaceFinderTest extends TestCase
{
    /** @var list<array{method: string, url: string, headers: list<string>, body: array<mixed>}> */
    private array $requests = [];

    protected function setUp(): void
    {
        $this->requests = [];
    }

    public function testItIsNotOperativeWithoutApiKey(): void
    {
        $sut = $this->finder($this->client(), apiKey: '');

        $this->assertFalse($sut->isOperative());
    }

    public function testItIsNotOperativeWhenDisabled(): void
    {
        $sut = $this->finder($this->client(), enabled: false);

        $this->assertFalse($sut->isOperative());
    }

    public function testDoesNotCallTheApiWhenItIsNotOperative(): void
    {
        $sut = $this->finder($this->client(), enabled: false);

        $this->assertSame([], $sut->suggest('Madrid', PlaceKind::Any, Locale::Spanish));
        $this->assertNull($sut->resolve('place-id', Locale::Spanish));
        $this->assertSame([], $this->requests);
    }

    public function testSuggestMapsThePredictionsOfTheResponse(): void
    {
        $sut = $this->finder($this->client($this->jsonResponse([
            'suggestions' => [[
                'placePrediction' => [
                    'placeId' => 'place-id',
                    'text' => ['text' => 'Madrid, España'],
                    'structuredFormat' => ['secondaryText' => ['text' => 'España']],
                ],
            ]],
        ])));

        $suggestions = $sut->suggest('Madrid', PlaceKind::Any, Locale::Spanish);

        $this->assertCount(1, $suggestions);
        $this->assertSame('place-id', $suggestions[0]->reference);
        $this->assertSame('Madrid, España', $suggestions[0]->label);
        $this->assertSame('España', $suggestions[0]->secondaryLabel);
    }

    public function testSuggestFallsBackToThePlaceIdWhenThereIsNoText(): void
    {
        $sut = $this->finder($this->client($this->jsonResponse([
            'suggestions' => [['placePrediction' => ['placeId' => 'place-id']]],
        ])));

        $suggestions = $sut->suggest('Madrid', PlaceKind::Any, Locale::Spanish);

        $this->assertSame('place-id', $suggestions[0]->label);
        $this->assertNull($suggestions[0]->secondaryLabel);
    }

    public function testSuggestSkipsEntriesWithoutAPlaceId(): void
    {
        $sut = $this->finder($this->client($this->jsonResponse([
            'suggestions' => [
                ['queryPrediction' => ['text' => ['text' => 'Madrid']]],
                ['placePrediction' => ['text' => ['text' => 'Madrid']]],
                ['placePrediction' => ['placeId' => 'place-id', 'text' => ['text' => 'Madrid']]],
            ],
        ])));

        $suggestions = $sut->suggest('Madrid', PlaceKind::Any, Locale::Spanish);

        $this->assertCount(1, $suggestions);
        $this->assertSame('place-id', $suggestions[0]->reference);
    }

    public function testSuggestReturnsNoSuggestionsWhenTheResponseHasNone(): void
    {
        $sut = $this->finder($this->client($this->jsonResponse([])));

        $this->assertSame([], $sut->suggest('Madrid', PlaceKind::Any, Locale::Spanish));
    }

    public function testSuggestSendsTheQueryLocaleAndRegionToTheApi(): void
    {
        $sut = $this->finder($this->client($this->jsonResponse([])), region: 'pt');

        $sut->suggest('Lisboa', PlaceKind::Any, Locale::English);

        $request = $this->lastRequest();

        $this->assertSame('POST', $request['method']);
        $this->assertSame('https://places.googleapis.com/v1/places:autocomplete', $request['url']);
        $this->assertSame(['input' => 'Lisboa', 'languageCode' => 'en', 'regionCode' => 'PT'], $request['body']);
        $this->assertContains('X-Goog-Api-Key: an-api-key', $request['headers']);
    }

    public function testSuggestRestrictsThePrimaryTypesOfEachPlaceKind(): void
    {
        $sut = $this->finder($this->client($this->jsonResponse([])));

        $sut->suggest('Madrid', PlaceKind::City, Locale::Spanish);
        $this->assertSame(['locality', 'postal_town'], $this->lastRequest()['body']['includedPrimaryTypes']);

        $sut->suggest('Madrid', PlaceKind::Lodging, Locale::Spanish);
        $this->assertSame(['lodging'], $this->lastRequest()['body']['includedPrimaryTypes']);

        $sut->suggest('Madrid', PlaceKind::Any, Locale::Spanish);
        $this->assertArrayNotHasKey('includedPrimaryTypes', $this->lastRequest()['body']);
    }

    public function testSuggestReturnsNoSuggestionsWhenTheApiFails(): void
    {
        $sut = $this->finder($this->client(new MockResponse('', ['http_code' => 500])));

        $this->assertSame([], $sut->suggest('Madrid', PlaceKind::Any, Locale::Spanish));
    }

    public function testResolveMapsThePlaceDetails(): void
    {
        $sut = $this->finder($this->client($this->jsonResponse([
            'id' => 'place-id',
            'displayName' => ['text' => 'Madrid'],
            'formattedAddress' => 'Madrid, España',
            'location' => ['latitude' => 40.4168, 'longitude' => -3.7038],
        ])));

        $place = $sut->resolve('place-id', Locale::Spanish);

        $this->assertNotNull($place);
        $this->assertSame('place-id', $place->reference);
        $this->assertSame('Madrid, España', $place->label);
        $this->assertSame(40.4168, $place->point->latitude);
        $this->assertSame(-3.7038, $place->point->longitude);
    }

    public function testResolveFallsBackToTheDisplayNameWhenThereIsNoAddress(): void
    {
        $sut = $this->finder($this->client($this->jsonResponse([
            'id' => 'place-id',
            'displayName' => ['text' => 'Madrid'],
            'location' => ['latitude' => 40.4168, 'longitude' => -3.7038],
        ])));

        $place = $sut->resolve('place-id', Locale::Spanish);

        $this->assertNotNull($place);
        $this->assertSame('Madrid', $place->label);
    }

    public function testResolveReturnsNothingWhenTheResponseHasNoCoordinates(): void
    {
        $sut = $this->finder($this->client($this->jsonResponse(['id' => 'place-id', 'formattedAddress' => 'Madrid'])));

        $this->assertNull($sut->resolve('place-id', Locale::Spanish));
    }

    public function testResolveReturnsNothingForAnEmptyReference(): void
    {
        $sut = $this->finder($this->client($this->jsonResponse([])));

        $this->assertNull($sut->resolve('', Locale::Spanish));
        $this->assertSame([], $this->requests);
    }

    public function testResolveReturnsNothingWhenTheApiFails(): void
    {
        $sut = $this->finder($this->client(new MockResponse('', ['http_code' => 404])));

        $this->assertNull($sut->resolve('place-id', Locale::Spanish));
    }

    public function testResolveEscapesTheReferenceAndAsksForTheLocale(): void
    {
        $sut = $this->finder($this->client($this->jsonResponse([])));

        $sut->resolve('place id/2', Locale::English);

        $request = $this->lastRequest();

        $this->assertSame('GET', $request['method']);
        $this->assertStringStartsWith('https://places.googleapis.com/v1/places/place%20id%2F2?', $request['url']);
        $this->assertStringContainsString('languageCode=en', $request['url']);
    }

    private function finder(
        HttpClientInterface $httpClient,
        string $apiKey = 'an-api-key',
        bool $enabled = true,
        string $region = 'es',
    ): GooglePlacesApiPlaceFinder {
        return new GooglePlacesApiPlaceFinder($httpClient, new NullLogger(), $apiKey, $enabled, $region);
    }

    private function client(?MockResponse $response = null): MockHttpClient
    {
        $response ??= $this->jsonResponse([]);

        return new MockHttpClient(function (string $method, string $url, array $options) use ($response): MockResponse {
            /** @var array<mixed> $body */
            $body = is_string($options['body'] ?? null) ? (array) json_decode($options['body'], true) : [];

            /** @var list<string> $headers */
            $headers = $options['headers'] ?? [];

            $this->requests[] = ['method' => $method, 'url' => $url, 'headers' => $headers, 'body' => $body];

            return $response;
        });
    }

    /** @param array<mixed> $data */
    private function jsonResponse(array $data): MockResponse
    {
        return new MockResponse((string) json_encode($data), ['response_headers' => ['content-type' => 'application/json']]);
    }

    /** @return array{method: string, url: string, headers: list<string>, body: array<mixed>} */
    private function lastRequest(): array
    {
        $lastRequest = $this->requests[count($this->requests) - 1] ?? null;

        $this->assertNotNull($lastRequest);

        return $lastRequest;
    }
}
