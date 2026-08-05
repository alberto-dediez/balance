<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Infrastructure\Geo\Google;

use App\CarbonFootprint\Domain\Model\Geo\DistanceSource;
use App\CarbonFootprint\Domain\Model\Geo\TravelMode;
use App\CarbonFootprint\Infrastructure\Geo\Google\GoogleRoutesApiDistanceCalculator;
use App\Shared\Domain\Model\Geo\GeoPoint;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class GoogleRoutesApiDistanceCalculatorTest extends TestCase
{
    /** @var list<array{method: string, url: string, headers: list<string>, body: array<mixed>}> */
    private array $requests = [];

    private GeoPoint $madrid;

    private GeoPoint $barcelona;

    protected function setUp(): void
    {
        $this->requests = [];
        $this->madrid = new GeoPoint(40.4168, -3.7038);
        $this->barcelona = new GeoPoint(41.3874, 2.1686);
    }

    public function testConvertsTheDistanceOfTheRouteToKilometers(): void
    {
        $sut = $this->calculator($this->client($this->jsonResponse([
            'routes' => [['distanceMeters' => 620500, 'duration' => '21600s']],
        ])));

        $distance = $sut->calculate($this->madrid, $this->barcelona, TravelMode::Driving);

        $this->assertNotNull($distance);
        $this->assertSame(620.5, $distance->kilometers);
        $this->assertSame(21600, $distance->durationSeconds);
        $this->assertSame(DistanceSource::Route, $distance->source);
    }

    public function testAcceptsARouteWithoutDuration(): void
    {
        $sut = $this->calculator($this->client($this->jsonResponse(['routes' => [['distanceMeters' => 1000]]])));

        $distance = $sut->calculate($this->madrid, $this->barcelona, TravelMode::Driving);

        $this->assertNotNull($distance);
        $this->assertSame(1.0, $distance->kilometers);
        $this->assertNull($distance->durationSeconds);
    }

    public function testIgnoresADurationThatIsNotExpressedInSeconds(): void
    {
        $sut = $this->calculator($this->client($this->jsonResponse([
            'routes' => [['distanceMeters' => 1000, 'duration' => '2h']],
        ])));

        $distance = $sut->calculate($this->madrid, $this->barcelona, TravelMode::Driving);

        $this->assertNotNull($distance);
        $this->assertNull($distance->durationSeconds);
    }

    public function testUsesTheFirstRouteOfTheResponse(): void
    {
        $sut = $this->calculator($this->client($this->jsonResponse([
            'routes' => [['distanceMeters' => 1000], ['distanceMeters' => 5000]],
        ])));

        $distance = $sut->calculate($this->madrid, $this->barcelona, TravelMode::Driving);

        $this->assertNotNull($distance);
        $this->assertSame(1.0, $distance->kilometers);
    }

    public function testReturnsNothingWhenThereAreNoRoutes(): void
    {
        $sut = $this->calculator($this->client($this->jsonResponse(['routes' => []])));

        $this->assertNull($sut->calculate($this->madrid, $this->barcelona, TravelMode::Driving));
    }

    public function testReturnsNothingWhenTheRouteHasNoDistance(): void
    {
        $sut = $this->calculator($this->client($this->jsonResponse(['routes' => [['duration' => '600s']]])));

        $this->assertNull($sut->calculate($this->madrid, $this->barcelona, TravelMode::Driving));
    }

    public function testReturnsNothingWhenTheApiFails(): void
    {
        $sut = $this->calculator($this->client(new MockResponse('', ['http_code' => 500])));

        $this->assertNull($sut->calculate($this->madrid, $this->barcelona, TravelMode::Driving));
    }

    public function testDoesNotCallTheApiForFlights(): void
    {
        $sut = $this->calculator($this->client());

        $this->assertNull($sut->calculate($this->madrid, $this->barcelona, TravelMode::Flight));
        $this->assertSame([], $this->requests);
    }

    public function testDoesNotCallTheApiWhenDisabled(): void
    {
        $sut = $this->calculator($this->client(), enabled: false);

        $this->assertNull($sut->calculate($this->madrid, $this->barcelona, TravelMode::Driving));
        $this->assertSame([], $this->requests);
    }

    public function testDoesNotCallTheApiWithoutApiKey(): void
    {
        $sut = $this->calculator($this->client(), apiKey: '');

        $this->assertNull($sut->calculate($this->madrid, $this->barcelona, TravelMode::Driving));
        $this->assertSame([], $this->requests);
    }

    public function testSendsTheOriginDestinationAndRegionToTheApi(): void
    {
        $sut = $this->calculator($this->client($this->jsonResponse([])), region: 'pt');

        $sut->calculate($this->madrid, $this->barcelona, TravelMode::Driving);

        $request = $this->lastRequest();

        $this->assertSame('POST', $request['method']);
        $this->assertSame('https://routes.googleapis.com/directions/v2:computeRoutes', $request['url']);
        $this->assertSame(
            ['latitude' => 40.4168, 'longitude' => -3.7038],
            $request['body']['origin']['location']['latLng'],
        );
        $this->assertSame(
            ['latitude' => 41.3874, 'longitude' => 2.1686],
            $request['body']['destination']['location']['latLng'],
        );
        $this->assertSame('PT', $request['body']['regionCode']);
        $this->assertContains('X-Goog-Api-Key: an-api-key', $request['headers']);
    }

    public function testTranslatesEachTravelModeToItsGoogleCounterpart(): void
    {
        $sut = $this->calculator($this->client($this->jsonResponse([])));

        $googleTravelModes = [
            TravelMode::Driving->value => 'DRIVE',
            TravelMode::Transit->value => 'TRANSIT',
            TravelMode::Cycling->value => 'BICYCLE',
            TravelMode::Walking->value => 'WALK',
        ];

        foreach ($googleTravelModes as $mode => $expected) {
            $sut->calculate($this->madrid, $this->barcelona, TravelMode::from((string) $mode));

            $this->assertSame($expected, $this->lastRequest()['body']['travelMode']);
        }
    }

    private function calculator(
        HttpClientInterface $httpClient,
        string $apiKey = 'an-api-key',
        bool $enabled = true,
        string $region = 'es',
    ): GoogleRoutesApiDistanceCalculator {
        return new GoogleRoutesApiDistanceCalculator($httpClient, new NullLogger(), $apiKey, $enabled, $region);
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
