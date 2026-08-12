<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\PublicLanding\GetActive;

use App\CarbonFootprint\Application\PublicLanding\GetActive\GetActivePublicLandingRequest;
use App\CarbonFootprint\Application\PublicLanding\GetActive\GetActivePublicLandingService;
use App\CarbonFootprint\Domain\Model\PublicLanding\PublicLandingOwnerType;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryPublicLandingRepository;
use App\EventOperations\Domain\Model\Event\EventId;
use App\Library\Infrastructure\Persistence\InMemory\Repository\InMemoryMediaRepository;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PublicLanding\PublicLandingMother;
use PHPUnit\Framework\TestCase;

class GetActivePublicLandingServiceTest extends TestCase
{
    private InMemoryPublicLandingRepository $publicLandingRepository;

    private GetActivePublicLandingService $sut;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->publicLandingRepository = new InMemoryPublicLandingRepository();

        $this->sut = new GetActivePublicLandingService(
            $this->publicLandingRepository,
            new InMemoryMediaRepository(),
        );
    }

    public function testReturnsTheActiveLandingOfTheOwner(): void
    {
        $eventId = (string) EventId::generate();
        $this->publicLandingRepository->add(PublicLandingMother::activeForEvent($eventId));

        $response = ($this->sut)(new GetActivePublicLandingRequest(PublicLandingOwnerType::Event, $eventId));

        $this->assertNotNull($response->publicLanding);
        $this->assertSame('Bienvenido', $response->publicLanding->title());
    }

    public function testReturnsNothingWhenThereIsNoLanding(): void
    {
        $response = ($this->sut)(
            new GetActivePublicLandingRequest(PublicLandingOwnerType::Event, (string) EventId::generate()),
        );

        $this->assertNull($response->publicLanding);
    }

    public function testReturnsNothingWhenTheLandingIsInactive(): void
    {
        $eventId = (string) EventId::generate();
        $publicLanding = PublicLandingMother::activeForEvent($eventId);
        $publicLanding->update(
            active: false,
            title: 'Bienvenido',
            subtitle: '',
            intro: '',
            heroImage: null,
            ctaLabel: '',
        );
        $this->publicLandingRepository->add($publicLanding);

        $response = ($this->sut)(new GetActivePublicLandingRequest(PublicLandingOwnerType::Event, $eventId));

        $this->assertNull($response->publicLanding);
    }

    public function testReturnsNothingWhenTheLandingIsActiveButEmpty(): void
    {
        $eventId = (string) EventId::generate();
        $publicLanding = PublicLandingMother::forEvent($eventId);
        $publicLanding->update(
            active: true,
            title: '',
            subtitle: '',
            intro: '',
            heroImage: null,
            ctaLabel: '',
        );
        $this->publicLandingRepository->add($publicLanding);

        $response = ($this->sut)(new GetActivePublicLandingRequest(PublicLandingOwnerType::Event, $eventId));

        $this->assertNull($response->publicLanding);
    }
}
