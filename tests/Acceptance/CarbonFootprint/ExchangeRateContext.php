<?php

declare(strict_types=1);

namespace App\Tests\Acceptance\CarbonFootprint;

use App\CarbonFootprint\Domain\Model\Activity\ActivityRepository;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ActivityPlacement;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\Combustible\CombustibleId;
use App\CarbonFootprint\Domain\Model\Combustible\CombustibleRepository;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintRepository;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\ExchangeRate\ExchangeRateFilter;
use App\CarbonFootprint\Domain\Model\ExchangeRate\ExchangeRateRepository;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\CarbonFootprint\Domain\Model\Unit;
use App\CarbonFootprint\Infrastructure\Gateway\InMemoryExchangeRateGateway;
use App\Property\Domain\Model\Property\PropertyId;
use App\Tests\Acceptance\Shared\AcceptanceClient;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\ExchangeRate\ExchangeRateMother;
use Behat\Behat\Context\Context;
use Behat\Hook\AfterScenario;
use Behat\Step\Given;
use Behat\Step\Then;
use PHPUnit\Framework\Assert;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

final class ExchangeRateContext implements Context
{
    private AcceptanceClient $client;

    public function __construct(KernelBrowser $kernelBrowser)
    {
        $this->client = AcceptanceClient::instance($kernelBrowser);
    }

    #[AfterScenario]
    public function after(): void
    {
        $this->getGateway()->reset();
    }

    #[Given('the ECB feed of :date contains currency :currency at rate :rate')]
    public function theEcbFeedContainsCurrency(string $date, string $currency, string $rate): void
    {
        $this->getGateway()->add($currency, $rate, new \DateTimeImmutable($date));
    }

    #[Given('the ECB feed is unavailable')]
    public function theEcbFeedIsUnavailable(): void
    {
        $this->getGateway()->fail();
    }

    #[Given('exchange rate :currency at rate :rate published on :date exists')]
    public function exchangeRateExists(string $currency, string $rate, string $date): void
    {
        $this->getRepository()->add(ExchangeRateMother::registered(
            currency: Unit::from($currency),
            date: $date,
            rate: $rate,
        ));
    }

    #[Given('combustible :id has an emission factor of :co2eq per :unit for year :year')]
    public function combustibleHasAnEmissionFactorPerUnit(string $id, string $co2eq, string $unit, string $year): void
    {
        $combustible = $this->getCombustibleRepository()->find(new CombustibleId($id));

        Assert::assertNotNull($combustible);

        $combustible->setEmissionFactor(new EmissionFactor(
            co2eq: $co2eq,
            co2: $co2eq,
            ch4: '0',
            n2o: '0',
            year: (int) $year,
            emissionFactorGroupId: EmissionFactorGroupMother::default()->id(),
            unit: Unit::from($unit),
        ));

        $this->getCombustibleRepository()->add($combustible);
    }

    #[Then('exchange rate :currency should be :rate published on :date')]
    public function exchangeRateShouldBe(string $currency, string $rate, string $date): void
    {
        $exchangeRate = $this->getRepository()->findByCurrency(Unit::from($currency));

        Assert::assertNotNull($exchangeRate);
        Assert::assertEqualsWithDelta((float) $rate, (float) $exchangeRate->rate(), 1e-9);
        Assert::assertSame($date, $exchangeRate->date()->format('Y-m-d'));
    }

    #[Then('exchange rate :currency should not exist')]
    public function exchangeRateShouldNotExist(string $currency): void
    {
        $unit = Unit::tryFrom($currency);

        if (null === $unit) {
            return;
        }

        Assert::assertNull($this->getRepository()->findByCurrency($unit));
    }

    #[Then('there should be :count exchange rate for :currency')]
    public function thereShouldBeExchangeRatesFor(int $count, string $currency): void
    {
        Assert::assertSame($count, $this->getRepository()->totalResultsByFilter(new ExchangeRateFilter(currency: Unit::from($currency))));
    }

    #[Then('the activities of source :source in scope :scope of company carbon footprint :id for property :propertyId should emit :co2eq')]
    public function theActivitiesShouldEmit(string $source, string $scope, string $id, string $propertyId, string $co2eq): void
    {
        $footprint = $this->getCompanyCarbonFootprintRepository()->find(new CompanyCarbonFootprintId($id));

        Assert::assertNotNull($footprint);

        $activities = $this->getActivityRepository()->findByIds($footprint->activityIds(new ActivityPlacement(
            Scope::from($scope),
            Source::from($source),
            propertyId: 'none' === $propertyId ? null : new PropertyId($propertyId),
        )));

        $total = 0.0;

        foreach ($activities as $activity) {
            $total += (float) $activity->emissions()->co2eq();
        }

        Assert::assertEqualsWithDelta((float) $co2eq, $total, 1e-6);
    }

    private function getGateway(): InMemoryExchangeRateGateway
    {
        return $this->client->getContainer()->get(InMemoryExchangeRateGateway::class);
    }

    private function getRepository(): ExchangeRateRepository
    {
        return $this->client->getContainer()->get(ExchangeRateRepository::class);
    }

    private function getCombustibleRepository(): CombustibleRepository
    {
        return $this->client->getContainer()->get(CombustibleRepository::class);
    }

    private function getCompanyCarbonFootprintRepository(): CompanyCarbonFootprintRepository
    {
        return $this->client->getContainer()->get(CompanyCarbonFootprintRepository::class);
    }

    private function getActivityRepository(): ActivityRepository
    {
        return $this->client->getContainer()->get(ActivityRepository::class);
    }
}
