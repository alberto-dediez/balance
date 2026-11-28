<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Contest;

use App\CarbonFootprint\Application\Contest\Register\RegisterContestRequest;
use App\CarbonFootprint\Application\Contest\Register\RegisterContestService;
use App\CarbonFootprint\Domain\Model\Contest\ContestAwardMode;
use App\CarbonFootprint\Domain\Model\Contest\ContestHostReference;
use App\CarbonFootprint\Domain\Model\Contest\ContestParticipationMode;
use App\CarbonFootprint\Domain\Model\Contest\ContestRegistered;
use App\CarbonFootprint\Domain\Model\Contest\ContestStatus;
use App\Property\Application\Property\PropertyAuthorizer;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountFeatures;
use App\Security\Domain\Model\Account\AccountId;
use App\Tests\Unit\Property\Application\DistributorAssignment\DistributedPropertiesPolicyMother;
use App\Tests\Unit\Property\Domain\Model\DistributorAssignment\DistributorAssignmentMother;
use App\Tests\Unit\Security\Application\Account\DistributorPolicyMother;
use App\Tests\Unit\Security\Domain\Model\Account\AccountMother;
use App\Tests\Unit\Shared\Application\CurrentAccount\StubCurrentAccountIdProvider;

class RegisterContestServiceTest extends ContestApplicationTestCase
{
    public function testRegistersADraftContestOfTheCurrentAccount(): void
    {
        $hosts = [$this->eventHost(), $this->propertyHost(), $this->tripLinkHost()];

        $response = $this->createSUT()($this->request($hosts), $this->owner());

        $this->assertInstanceOf(ContestRegistered::class, $this->spySubscriber->lastEvent());
        $this->assertSame(ContestStatus::Draft, $response->contest->status());
        $this->assertTrue($response->contest->accountId()?->equals(new AccountId(self::ACCOUNT)));
        $this->assertCount(3, $response->contest->hostReferences());
        $this->assertNotNull($this->contestRepository->find($response->contest->id()));
    }

    public function testCalculatorOfAnotherAccountIsDenied(): void
    {
        $this->expectException(UnauthorizedActionException::class);

        $this->createSUT()($this->request([$this->eventHost(self::OTHER_ACCOUNT)]), $this->owner());
    }

    public function testAdminWithoutImpersonatingIsDenied(): void
    {
        $this->expectException(UnauthorizedActionException::class);

        $this->createSUT()($this->request([$this->eventHost()]), $this->admin());
    }

    public function testDistributedPropertyCannotBeAssociated(): void
    {
        $distributor = AccountMother::distributorAccountFromNameAndId('Distribuidor', 'distributor');
        $distributor->changeFeatures(new AccountFeatures(contests: true));
        $hotel = AccountMother::fromNameAndId('Hotel', 'hotel');
        $this->accountRepository->add($distributor);
        $this->accountRepository->add($hotel);
        $host = $this->propertyHost('hotel');
        $property = $this->propertyRepository->find(new PropertyId($host->hostId));
        $this->assertNotNull($property);

        $accounts = [$distributor, $hotel];
        $propertyAuthorizer = new PropertyAuthorizer(
            currentAccountIdProvider: new StubCurrentAccountIdProvider($distributor->id()),
            accountFeaturesPolicy: $this->featuresPolicy(),
            distributedPropertiesPolicy: DistributedPropertiesPolicyMother::create($accounts, [$property], [DistributorAssignmentMother::forAccount($distributor->id(), $hotel->id())]),
            distributorPolicy: DistributorPolicyMother::create($accounts),
        );

        $sut = new RegisterContestService(
            $this->contestRepository,
            $this->authorizer('distributor'),
            $this->hostLocator('distributor', $propertyAuthorizer),
            new StubCurrentAccountIdProvider($distributor->id()),
        );

        $this->expectException(UnauthorizedActionException::class);

        $sut($this->request([$host]), $this->owner('distributor'));
    }

    /** @param list<ContestHostReference> $hosts */
    private function request(array $hosts): RegisterContestRequest
    {
        return new RegisterContestRequest(
            participationMode: ContestParticipationMode::Calculation,
            awardMode: ContestAwardMode::Draw,
            winnersCount: 1,
            platformDraw: true,
            adultsOnly: true,
            name: 'Concurso',
            description: 'Descripción',
            prizeDescription: 'Premio',
            terms: 'Bases',
            organizerName: 'Organizador',
            organizerTaxId: 'B1',
            organizerContactEmail: 'org@example.com',
            privacyPolicyUrl: null,
            startsAt: new \DateTimeImmutable('-1 day'),
            endsAt: new \DateTimeImmutable('+10 days'),
            hosts: $hosts,
            allowedCountries: ['ES'],
        );
    }

    private function createSUT(): RegisterContestService
    {
        return new RegisterContestService(
            $this->contestRepository,
            $this->authorizer(),
            $this->hostLocator(),
            new StubCurrentAccountIdProvider(new AccountId(self::ACCOUNT)),
        );
    }
}
