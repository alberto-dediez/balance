<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Infrastructure\UI\Web\Public;

use App\CarbonFootprint\Infrastructure\UI\Web\Public\PublicFormRateLimiter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\RateLimiter\Storage\InMemoryStorage;

class PublicFormRateLimiterTest extends TestCase
{
    public function testAcceptsARequestUnderTheLimits(): void
    {
        $sut = $this->createSUT();

        $this->assertNull($sut->check($this->request()));
    }

    public function testRejectsTheRequestsOverTheTokenLimit(): void
    {
        $sut = $this->createSUT(tokenLimit: 2);
        $request = $this->request();

        $this->assertNull($sut->check($request));
        $this->assertNull($sut->check($request));

        $response = $sut->check($request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(Response::HTTP_TOO_MANY_REQUESTS, $response->getStatusCode());
    }

    public function testRejectsTheRequestsOnceTheInvalidTokenLimitIsExhausted(): void
    {
        $sut = $this->createSUT(invalidTokenLimit: 2);
        $request = $this->request();

        $sut->penalizeInvalidToken($request);
        $this->assertNull($sut->check($request));

        $sut->penalizeInvalidToken($request);
        $response = $sut->check($request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(Response::HTTP_TOO_MANY_REQUESTS, $response->getStatusCode());
    }

    public function testCountsTheRequestsOfEachClientIpSeparately(): void
    {
        $sut = $this->createSUT(tokenLimit: 1);

        $this->assertNull($sut->check($this->request('1.2.3.4')));
        $this->assertNull($sut->check($this->request('5.6.7.8')));
        $this->assertNotNull($sut->check($this->request('1.2.3.4')));
    }

    public function testPenalizingOneClientDoesNotAffectTheOthers(): void
    {
        $sut = $this->createSUT(invalidTokenLimit: 1);

        $sut->penalizeInvalidToken($this->request('1.2.3.4'));

        $this->assertNotNull($sut->check($this->request('1.2.3.4')));
        $this->assertNull($sut->check($this->request('5.6.7.8')));
    }

    public function testTheTokenLimitDoesNotConsumeThePlacesLimit(): void
    {
        $sut = $this->createSUT(tokenLimit: 1, placesLimit: 1);
        $request = $this->request();

        $this->assertNull($sut->check($request));
        $this->assertTrue($sut->checkPlaces($request));
        $this->assertFalse($sut->checkPlaces($request));
    }

    public function testRejectsThePlacesRequestsOverTheLimit(): void
    {
        $sut = $this->createSUT(placesLimit: 2);
        $request = $this->request();

        $this->assertTrue($sut->checkPlaces($request));
        $this->assertTrue($sut->checkPlaces($request));
        $this->assertFalse($sut->checkPlaces($request));
    }

    public function testFallsBackToASharedBucketWithoutClientIp(): void
    {
        $sut = $this->createSUT(tokenLimit: 1);
        $request = new Request();

        $this->assertNull($sut->check($request));
        $this->assertNotNull($sut->check($request));
    }

    private function request(string $clientIp = '1.2.3.4'): Request
    {
        return Request::create('/', 'GET', [], [], [], ['REMOTE_ADDR' => $clientIp]);
    }

    private function createSUT(int $tokenLimit = 100, int $invalidTokenLimit = 10, int $placesLimit = 30): PublicFormRateLimiter
    {
        return new PublicFormRateLimiter(
            $this->limiterFactory('token', $tokenLimit),
            $this->limiterFactory('invalid_token', $invalidTokenLimit),
            $this->limiterFactory('places', $placesLimit),
        );
    }

    private function limiterFactory(string $id, int $limit): RateLimiterFactory
    {
        return new RateLimiterFactory(
            ['id' => $id, 'policy' => 'sliding_window', 'limit' => $limit, 'interval' => '1 minute'],
            new InMemoryStorage(),
        );
    }
}
