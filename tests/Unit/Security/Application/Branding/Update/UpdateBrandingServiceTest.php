<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Application\Branding\Update;

use App\Library\Domain\Model\Media\MediaId;
use App\Security\Application\Branding\Update\UpdateBrandingRequest;
use App\Security\Application\Branding\Update\UpdateBrandingService;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Branding\BrandingChanged;
use App\Security\Domain\Model\Branding\FontFamilyName;
use App\Security\Domain\Model\Branding\HexColor;
use App\Security\Domain\Model\Branding\PdfFontFamily;
use App\Security\Domain\Model\User\Role;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryBrandingRepository;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Security\Application\Branding\BrandingAuthorizerMother;
use App\Tests\Unit\Security\Domain\Model\Account\AccountMother;
use App\Tests\Unit\Security\Domain\Model\Branding\BrandingMother;
use App\Tests\Unit\Security\Domain\Model\Branding\BrandingRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class UpdateBrandingServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testItStoresLogoColoursAndFonts(): void
    {
        $repository = BrandingRepositoryMother::inMemory();

        $account = AccountMother::fromName('Acme');
        $branding = BrandingMother::forAccount($account->id());
        $repository->add($branding);

        $logoMediaId = MediaId::generate();

        $result = $this->createSUT($repository)(
            new UpdateBrandingRequest(
                brandingId: $branding->id(),
                logoMediaId: $logoMediaId,
                primary: new HexColor('#8A2432'),
                webFontFamily: new FontFamilyName('Poppins'),
                webFontWeights: '400;700',
                pdfFontFamily: PdfFontFamily::Inter,
            ),
            UserMother::adminUser(),
        );

        $persisted = $repository->find($result->brandingId);

        $this->assertNotNull($persisted);
        $this->assertNotNull($persisted->logoMediaId());
        $this->assertTrue($logoMediaId->equals($persisted->logoMediaId()));
        $this->assertNotNull($persisted->primary());
        $this->assertSame('#8A2432', $persisted->primary()->value);
        $this->assertNull($persisted->secondary());
        $this->assertNotNull($persisted->webFontFamily());
        $this->assertSame('Poppins', $persisted->webFontFamily()->value);
        $this->assertSame('400;700', $persisted->webFontWeights());
        $this->assertSame(PdfFontFamily::Inter, $persisted->pdfFontFamily());

        $this->assertInstanceOf(BrandingChanged::class, $this->spySubscriber->lastEvent());
    }

    public function testNullColourClearsThePreviousValue(): void
    {
        $repository = BrandingRepositoryMother::inMemory();

        $account = AccountMother::fromName('Acme');
        $branding = BrandingMother::brandedForAccount($account->id());
        $repository->add($branding);

        $this->createSUT($repository)(
            new UpdateBrandingRequest(brandingId: $branding->id()),
            UserMother::adminUser(),
        );

        $persisted = $repository->find($branding->id());

        $this->assertNotNull($persisted);
        $this->assertNull($persisted->primary());
        $this->assertNull($persisted->webFontFamily());
        $this->assertNull($persisted->logoMediaId());
    }

    public function testOwnerOfTheAccountCanUpdate(): void
    {
        $repository = BrandingRepositoryMother::inMemory();

        $account = AccountMother::fromName('Acme');
        $branding = BrandingMother::forAccount($account->id());
        $repository->add($branding);

        $owner = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $account->id());

        $result = $this->createSUT($repository)(new UpdateBrandingRequest($branding->id()), $owner);

        $this->assertTrue($branding->id()->equals($result->brandingId));
    }

    public function testOwnerOfAnotherAccountCannotUpdate(): void
    {
        $this->expectException(UnauthorizedActionException::class);

        $repository = BrandingRepositoryMother::inMemory();

        $branding = BrandingMother::forAccount(AccountMother::fromName('Acme')->id());
        $repository->add($branding);

        $stranger = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, AccountMother::fromName('Other')->id());

        $this->createSUT($repository)(new UpdateBrandingRequest($branding->id()), $stranger);
    }

    public function testUnknownBrandingThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $repository = BrandingRepositoryMother::inMemory();

        $this->createSUT($repository)(
            new UpdateBrandingRequest(BrandingMother::random()->id()),
            UserMother::adminUser(),
        );
    }

    public function testIsHandled(): void
    {
        $sut = $this->createSUT(BrandingRepositoryMother::inMemory());

        $this->assertTrue($sut->isHandled(new UpdateBrandingRequest(BrandingMother::random()->id())));
    }

    private function createSUT(InMemoryBrandingRepository $repository): UpdateBrandingService
    {
        return new UpdateBrandingService($repository, BrandingAuthorizerMother::create());
    }
}
