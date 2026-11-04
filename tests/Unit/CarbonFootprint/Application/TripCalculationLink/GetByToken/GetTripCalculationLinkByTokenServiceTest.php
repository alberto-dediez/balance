<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\TripCalculationLink\GetByToken;

use App\CarbonFootprint\Application\Trip\PropertyLocator;
use App\CarbonFootprint\Application\TripCalculationLink\GetByToken\GetTripCalculationLinkByTokenRequest;
use App\CarbonFootprint\Application\TripCalculationLink\GetByToken\GetTripCalculationLinkByTokenService;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLink;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLinkTokenInvalidException;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryMobilityRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryTripCalculationLinkRepository;
use App\Property\Domain\Model\Property\PropertyId;
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

    private function linkToPropertyOfAccount(string $accountId): TripCalculationLink
    {
        $property = PropertyMother::fromNameAndAccountAndId('Hotel', $accountId, (string) PropertyId::generate());
        $this->propertyRepository->add($property);

        $link = TripCalculationLinkMother::fromPropertyIdAndAccount((string) $property->id(), $accountId);
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
