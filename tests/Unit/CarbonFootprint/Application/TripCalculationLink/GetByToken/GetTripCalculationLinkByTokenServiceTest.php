<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\TripCalculationLink\GetByToken;

use App\CarbonFootprint\Application\Trip\PropertyLocator;
use App\CarbonFootprint\Application\TripCalculationLink\GetByToken\GetTripCalculationLinkByTokenRequest;
use App\CarbonFootprint\Application\TripCalculationLink\GetByToken\GetTripCalculationLinkByTokenService;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLink;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLinkId;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLinkTokenInvalidException;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationScope;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryMobilityRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryTripCalculationLinkRepository;
use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Domain\Model\Property\PropertyType;
use App\Property\Infrastructure\Persistence\InMemory\Repository\InMemoryPropertyRepository;
use App\Security\Domain\Model\Account\Account;
use App\Security\Domain\Model\Account\AccountFeatures;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryAccountRepository;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Infrastructure\Geo\NullPlaceFinder;
use App\Tests\Unit\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLinkMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyMother;
use App\Tests\Unit\Security\Application\Account\AccountFeaturesPolicyMother;
use App\Tests\Unit\Security\Domain\Model\Account\AccountMother;
use PHPUnit\Framework\TestCase;

class GetTripCalculationLinkByTokenServiceTest extends TestCase
{
    private InMemoryTripCalculationLinkRepository $tripCalculationLinkRepository;
    private InMemoryPropertyRepository $propertyRepository;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->tripCalculationLinkRepository = new InMemoryTripCalculationLinkRepository();
        $this->propertyRepository = new InMemoryPropertyRepository();
    }

    public function testReturnsTheAccountLinkWithAccountTripCalculator(): void
    {
        $account = AccountMother::withFeatures('MyAccount', 'account-id', new AccountFeatures());
        $link = TripCalculationLinkMother::forAccount('account-id');
        $this->tripCalculationLinkRepository->add($link);

        $response = $this->createSUT($account)(new GetTripCalculationLinkByTokenRequest($link->token()));

        $this->assertSame($link, $response->tripCalculationLink);
        $this->assertSame('MyAccount', $response->ownerName);
        $this->assertFalse($response->assumesOvernightStay);
    }

    public function testThrowsForTheAccountLinkWithoutAccountTripCalculator(): void
    {
        $account = AccountMother::withFeatures('MyAccount', 'account-id', new AccountFeatures(accountTripCalculator: false));
        $link = TripCalculationLinkMother::forAccount('account-id');
        $this->tripCalculationLinkRepository->add($link);

        $this->expectException(TripCalculationLinkTokenInvalidException::class);

        $this->createSUT($account)(new GetTripCalculationLinkByTokenRequest($link->token()));
    }

    public function testReturnsThePropertyLinkWithPropertyTripCalculators(): void
    {
        $account = AccountMother::withFeatures('MyAccount', 'account-id', new AccountFeatures());
        $link = $this->linkToPropertyOfAccount('account-id');

        $response = $this->createSUT($account)(new GetTripCalculationLinkByTokenRequest($link->token()));

        $this->assertSame($link, $response->tripCalculationLink);
        $this->assertSame('Hotel', $response->ownerName);
    }

    public function testThrowsForThePropertyLinkWithoutPropertyTripCalculators(): void
    {
        $account = AccountMother::withFeatures('MyAccount', 'account-id', new AccountFeatures(propertyTripCalculators: false));
        $link = $this->linkToPropertyOfAccount('account-id');

        $this->expectException(TripCalculationLinkTokenInvalidException::class);

        $this->createSUT($account)(new GetTripCalculationLinkByTokenRequest($link->token()));
    }

    public function testAssumesTheOvernightStayForAnAccommodation(): void
    {
        $account = AccountMother::withFeatures('MyAccount', 'account-id', new AccountFeatures());
        $link = $this->linkToPropertyOfAccount('account-id', PropertyType::Hotel);

        $response = $this->createSUT($account)(new GetTripCalculationLinkByTokenRequest($link->token()));

        $this->assertTrue($response->assumesOvernightStay);
    }

    public function testDoesNotAssumeTheOvernightStayForAPropertyOfOtherType(): void
    {
        $account = AccountMother::withFeatures('MyAccount', 'account-id', new AccountFeatures());
        $link = $this->linkToPropertyOfAccount('account-id', PropertyType::Other);

        $response = $this->createSUT($account)(new GetTripCalculationLinkByTokenRequest($link->token()));

        $this->assertFalse($response->assumesOvernightStay);
    }

    public function testDoesNotAssumeTheOvernightStayWithOvernightStayScope(): void
    {
        $account = AccountMother::withFeatures('MyAccount', 'account-id', new AccountFeatures());
        $link = $this->linkToPropertyOfAccount('account-id', PropertyType::Hotel, TripCalculationScope::OvernightStay);

        $response = $this->createSUT($account)(new GetTripCalculationLinkByTokenRequest($link->token()));

        $this->assertFalse($response->assumesOvernightStay);
    }

    private function linkToPropertyOfAccount(string $accountId, ?PropertyType $propertyType = PropertyType::Hotel, ?TripCalculationScope $scope = null): TripCalculationLink
    {
        $property = PropertyMother::fromNameAndAccountAndIdAndType('Hotel', $accountId, (string) PropertyId::generate(), $propertyType);
        $this->propertyRepository->add($property);

        $link = TripCalculationLinkMother::from(
            propertyId: (string) $property->id(),
            id: (string) TripCalculationLinkId::generate(),
            accountId: $accountId,
            scope: $scope,
        );
        $this->tripCalculationLinkRepository->add($link);

        return $link;
    }

    private function createSUT(Account $account): GetTripCalculationLinkByTokenService
    {
        return new GetTripCalculationLinkByTokenService(
            $this->tripCalculationLinkRepository,
            $this->propertyRepository,
            new InMemoryMobilityRepository(),
            new PropertyLocator(),
            new NullPlaceFinder(),
            new InMemoryAccountRepository([(string) $account->id() => $account]),
            AccountFeaturesPolicyMother::create([$account]),
        );
    }
}
