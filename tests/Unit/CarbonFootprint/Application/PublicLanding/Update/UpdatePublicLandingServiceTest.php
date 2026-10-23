<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\PublicLanding\Update;

use App\CarbonFootprint\Application\PublicLanding\PublicLandingAuthorizer;
use App\CarbonFootprint\Application\PublicLanding\Update\PublicLandingBlockData;
use App\CarbonFootprint\Application\PublicLanding\Update\UpdatePublicLandingRequest;
use App\CarbonFootprint\Application\PublicLanding\Update\UpdatePublicLandingService;
use App\CarbonFootprint\Application\TripCalculationLink\TripCalculationLinkAuthorizer;
use App\CarbonFootprint\Domain\Model\PublicLanding\PublicLanding;
use App\CarbonFootprint\Domain\Model\PublicLanding\PublicLandingId;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryPublicLandingRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryTripCalculationLinkRepository;
use App\EventOperations\Application\Event\EventAuthorizer;
use App\EventOperations\Infrastructure\Persistence\InMemory\Repository\InMemoryEventRepository;
use App\Property\Application\Property\PropertyAuthorizer;
use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Infrastructure\Persistence\InMemory\Repository\InMemoryPropertyRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Domain\Model\Locale\LocaleGetter;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PublicLanding\PublicLandingMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class UpdatePublicLandingServiceTest extends TestCase
{
    private InMemoryPublicLandingRepository $publicLandingRepository;

    private InMemoryPropertyRepository $propertyRepository;

    private UpdatePublicLandingService $sut;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        LocaleGetter::setLocale(null);

        $this->publicLandingRepository = new InMemoryPublicLandingRepository();
        $this->propertyRepository = new InMemoryPropertyRepository();

        $this->sut = new UpdatePublicLandingService(
            $this->publicLandingRepository,
            new PublicLandingAuthorizer(
                $this->propertyRepository,
                new PropertyAuthorizer(),
                new InMemoryEventRepository(),
                new EventAuthorizer(),
                new InMemoryTripCalculationLinkRepository(),
                new TripCalculationLinkAuthorizer($this->propertyRepository, new PropertyAuthorizer()),
            ),
        );
    }

    protected function tearDown(): void
    {
        LocaleGetter::setLocale(null);
    }

    public function testUpdatesTheContentOfTheLanding(): void
    {
        $publicLanding = $this->storedLanding();

        $response = ($this->sut)(
            new UpdatePublicLandingRequest(
                publicLandingId: $publicLanding->id(),
                active: true,
                title: 'Bienvenido',
                subtitle: 'Calcula tu huella',
                intro: 'Medimos el impacto de tu viaje.',
                ctaLabel: 'Empezar',
                blocks: [new PublicLandingBlockData(title: 'Primero', text: 'Texto uno')],
            ),
            $this->ownerUser(),
        );

        $this->assertTrue($response->publicLanding->isActive());
        $this->assertSame('Bienvenido', $response->publicLanding->title());
        $this->assertCount(1, $response->publicLanding->blocks());
        $this->assertSame('Primero', $response->publicLanding->blocks()[0]->title());
    }

    public function testDropsEmptyBlocks(): void
    {
        $publicLanding = $this->storedLanding();

        $response = ($this->sut)(
            new UpdatePublicLandingRequest(
                publicLandingId: $publicLanding->id(),
                title: 'Bienvenido',
                blocks: [
                    new PublicLandingBlockData(title: 'Primero', text: 'Texto uno'),
                    new PublicLandingBlockData(),
                    new PublicLandingBlockData(title: 'Tercero', text: 'Texto tres'),
                ],
            ),
            $this->ownerUser(),
        );

        $this->assertCount(2, $response->publicLanding->blocks());
        $this->assertSame('Primero', $response->publicLanding->blocks()[0]->title());
        $this->assertSame('Tercero', $response->publicLanding->blocks()[1]->title());
    }

    public function testRemovesTheBlocksNoLongerSent(): void
    {
        $publicLanding = $this->storedLanding();

        ($this->sut)(
            new UpdatePublicLandingRequest(
                publicLandingId: $publicLanding->id(),
                title: 'Bienvenido',
                blocks: [
                    new PublicLandingBlockData(title: 'Primero', text: 'Texto uno'),
                    new PublicLandingBlockData(title: 'Segundo', text: 'Texto dos'),
                ],
            ),
            $this->ownerUser(),
        );

        $response = ($this->sut)(
            new UpdatePublicLandingRequest(
                publicLandingId: $publicLanding->id(),
                title: 'Bienvenido',
                blocks: [new PublicLandingBlockData(title: 'Primero', text: 'Texto uno')],
            ),
            $this->ownerUser(),
        );

        $this->assertCount(1, $response->publicLanding->blocks());
    }

    public function testEditingInAnotherLocaleOnlyWritesTheTranslation(): void
    {
        $publicLanding = $this->storedLanding();

        ($this->sut)(
            new UpdatePublicLandingRequest(
                publicLandingId: $publicLanding->id(),
                active: true,
                title: 'Bienvenido',
                ctaLabel: 'Empezar',
                blocks: [new PublicLandingBlockData(title: 'Primero', text: 'Texto uno')],
            ),
            $this->ownerUser(),
        );

        LocaleGetter::setLocale(Locale::English);

        ($this->sut)(
            new UpdatePublicLandingRequest(
                publicLandingId: $publicLanding->id(),
                active: true,
                title: 'Welcome',
                ctaLabel: 'Start',
                blocks: [new PublicLandingBlockData(title: 'First', text: 'Text one')],
            ),
            $this->ownerUser(),
        );

        $this->assertSame('Welcome', $publicLanding->title());
        $this->assertSame('First', $publicLanding->blocks()[0]->title());

        LocaleGetter::setLocale(Locale::Spanish);

        $this->assertSame('Bienvenido', $publicLanding->title());
        $this->assertSame('Primero', $publicLanding->blocks()[0]->title());
    }

    public function testThrowsWhenTheLandingDoesNotExist(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ($this->sut)(
            new UpdatePublicLandingRequest(publicLandingId: PublicLandingId::generate()),
            $this->ownerUser(),
        );
    }

    public function testDeniesAnOwnerFromAnotherAccount(): void
    {
        $publicLanding = $this->storedLanding();

        $this->expectException(UnauthorizedActionException::class);

        ($this->sut)(
            new UpdatePublicLandingRequest(publicLandingId: $publicLanding->id(), title: 'Intruso'),
            UserMother::fromUsernameAndRoleAndAccount('intruder', Role::Owner, new AccountId('another-account-id')),
        );
    }

    private function storedLanding(): PublicLanding
    {
        $property = PropertyMother::fromNameAndAccountAndId('Hotel', 'account-id', (string) PropertyId::generate());
        $this->propertyRepository->add($property);

        $publicLanding = PublicLandingMother::forProperty((string) $property->id());
        $this->publicLandingRepository->add($publicLanding);

        return $publicLanding;
    }

    private function ownerUser(): User
    {
        return UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id'));
    }
}
