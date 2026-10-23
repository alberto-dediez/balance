<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Geo\Google;

use App\Shared\Domain\Model\Geo\GeoBounds;
use App\Shared\Domain\Model\Geo\GeoPoint;
use App\Shared\Domain\Model\Geo\PlaceKind;
use App\Shared\Domain\Model\Geo\PlaceSearchArea;
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

        $sut->suggest('Valencia', PlaceKind::Region, Locale::Spanish);
        $this->assertSame(['(regions)'], $this->lastRequest()['body']['includedPrimaryTypes']);

        $sut->suggest('Madrid', PlaceKind::Any, Locale::Spanish);
        $this->assertArrayNotHasKey('includedPrimaryTypes', $this->lastRequest()['body']);
    }

    public function testSuggestRestrictsTheRegionCodesOfTheSearchArea(): void
    {
        $sut = $this->finder($this->client($this->jsonResponse([])));

        $sut->suggest('Madrid', PlaceKind::City, Locale::Spanish, new PlaceSearchArea(['es', 'pt']));

        $body = $this->lastRequest()['body'];

        $this->assertSame(['es', 'pt'], $body['includedRegionCodes']);
        $this->assertArrayNotHasKey('locationRestriction', $body);
    }

    public function testSuggestRestrictsTheRectangleOfTheSearchArea(): void
    {
        $sut = $this->finder($this->client($this->jsonResponse([])));

        $sut->suggest(
            'Valencia',
            PlaceKind::City,
            Locale::Spanish,
            new PlaceSearchArea(bounds: new GeoBounds(new GeoPoint(37.8, -1.5), new GeoPoint(40.8, 0.7))),
        );

        $body = $this->lastRequest()['body'];

        $this->assertSame([
            'rectangle' => [
                'low' => ['latitude' => 37.8, 'longitude' => -1.5],
                'high' => ['latitude' => 40.8, 'longitude' => 0.7],
            ],
        ], $body['locationRestriction']);
        $this->assertArrayNotHasKey('includedRegionCodes', $body);
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

    public function testResolveAsksForTheViewportAndTheAddressComponents(): void
    {
        $sut = $this->finder($this->client($this->jsonResponse([])));

        $sut->resolve('place-id', Locale::Spanish);

        $this->assertContains(
            'X-Goog-FieldMask: id,displayName,formattedAddress,location,viewport,addressComponents',
            $this->lastRequest()['headers'],
        );
    }

    public function testResolveMapsTheNameViewportAreasAndCountry(): void
    {
        $sut = $this->finder($this->client($this->jsonResponse([
            'id' => 'place-id',
            'displayName' => ['text' => 'Valencia'],
            'formattedAddress' => 'Valencia, España',
            'location' => ['latitude' => 39.47, 'longitude' => -0.37],
            'viewport' => [
                'low' => ['latitude' => 39.4, 'longitude' => -0.43],
                'high' => ['latitude' => 39.5, 'longitude' => -0.3],
            ],
            'addressComponents' => [
                ['longText' => 'Valencia', 'shortText' => 'Valencia', 'types' => ['locality', 'political']],
                ['longText' => 'Valencia', 'shortText' => 'V', 'types' => ['administrative_area_level_2', 'political']],
                ['longText' => 'Comunitat Valenciana', 'shortText' => 'CV', 'types' => ['administrative_area_level_1', 'political']],
                ['longText' => 'España', 'shortText' => 'ES', 'types' => ['country', 'political']],
                ['longText' => '46001', 'shortText' => '46001', 'types' => ['postal_code']],
            ],
        ])));

        $place = $sut->resolve('place-id', Locale::Spanish);

        $this->assertNotNull($place);
        $this->assertSame('Valencia', $place->name);
        $this->assertNotNull($place->viewport);
        $this->assertSame(39.4, $place->viewport->low->latitude);
        $this->assertSame(-0.43, $place->viewport->low->longitude);
        $this->assertSame(39.5, $place->viewport->high->latitude);
        $this->assertSame(-0.3, $place->viewport->high->longitude);
        $this->assertSame(['Valencia', 'Comunitat Valenciana', 'España', '46001'], $place->areaNames);
        $this->assertSame('ES', $place->countryCode);
    }

    public function testResolveCollectsTheNamesOfTheMinorAreasOfThePlace(): void
    {
        $sut = $this->finder($this->client($this->jsonResponse([
            'id' => 'place-id',
            'formattedAddress' => 'Ruzafa, Valencia, España',
            'location' => ['latitude' => 39.46, 'longitude' => -0.37],
            'addressComponents' => [
                ['longText' => 'Calle Sueca', 'shortText' => 'C. Sueca', 'types' => ['route']],
                ['longText' => 'Ruzafa', 'shortText' => 'Ruzafa', 'types' => ['sublocality_level_1', 'sublocality', 'political']],
                ['longText' => 'L\'Eixample', 'shortText' => 'L\'Eixample', 'types' => ['administrative_area_level_4', 'political']],
                ['longText' => 'Distrito 2', 'shortText' => 'D2', 'types' => ['administrative_area_level_5', 'political']],
                ['longText' => 'Huerta de Valencia', 'shortText' => 'Huerta', 'types' => ['colloquial_area', 'political']],
                ['longText' => '46006', 'shortText' => '46006', 'types' => ['postal_code']],
            ],
        ])));

        $place = $sut->resolve('place-id', Locale::Spanish);

        $this->assertNotNull($place);
        $this->assertSame(['Ruzafa', 'L\'Eixample', 'Distrito 2', 'Huerta de Valencia', '46006'], $place->areaNames);
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
