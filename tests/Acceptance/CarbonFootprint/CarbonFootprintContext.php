<?php

declare(strict_types=1);

namespace App\Tests\Acceptance\CarbonFootprint;

use App\CarbonFootprint\Domain\Model\CateringItem\CateringItemRepository;
use App\CarbonFootprint\Domain\Model\Combustible\CombustibleRepository;
use App\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketerRepository;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\GdOFactor\GdOFactorRepository;
use App\CarbonFootprint\Domain\Model\MachineryFuel\MachineryFuelRepository;
use App\CarbonFootprint\Domain\Model\Material\MaterialRepository;
use App\CarbonFootprint\Domain\Model\MerchandisingItem\MerchandisingItemRepository;
use App\CarbonFootprint\Domain\Model\Mobility\MobilityRepository;
use App\CarbonFootprint\Domain\Model\RoadTransport\RoadTransportRepository;
use App\CarbonFootprint\Domain\Model\Waste\WasteRepository;
use App\Tests\Acceptance\Shared\AcceptanceClient;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CateringItem\CateringItemMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Combustible\CombustibleMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketerMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\GdOFactor\GdOFactorMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\MachineryFuel\MachineryFuelMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Material\MaterialMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\MerchandisingItem\MerchandisingItemMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Mobility\MobilityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\RoadTransport\RoadTransportMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Waste\WasteMother;
use Behat\Behat\Context\Context;
use Behat\Step\Given;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

final readonly class CarbonFootprintContext implements Context
{
    private AcceptanceClient $client;

    public function __construct(
        KernelBrowser $kernelBrowser,
    ) {
        $this->client = AcceptanceClient::instance($kernelBrowser);
    }

    public function getMaterialRepository(): MaterialRepository
    {
        return $this->client->getContainer()->get(MaterialRepository::class);
    }

    public function getMerchandisingItemRepository(): MerchandisingItemRepository
    {
        return $this->client->getContainer()->get(MerchandisingItemRepository::class);
    }

    public function getCateringItemRepository(): CateringItemRepository
    {
        return $this->client->getContainer()->get(CateringItemRepository::class);
    }

    public function getCombustibleRepository(): CombustibleRepository
    {
        return $this->client->getContainer()->get(CombustibleRepository::class);
    }

    public function getRoadTransportRepository(): RoadTransportRepository
    {
        return $this->client->getContainer()->get(RoadTransportRepository::class);
    }

    public function getElectricityMarketerRepository(): ElectricityMarketerRepository
    {
        return $this->client->getContainer()->get(ElectricityMarketerRepository::class);
    }

    public function getGdOFactorRepository(): GdOFactorRepository
    {
        return $this->client->getContainer()->get(GdOFactorRepository::class);
    }

    public function getMachineryFuelRepository(): MachineryFuelRepository
    {
        return $this->client->getContainer()->get(MachineryFuelRepository::class);
    }

    public function getMobilityRepository(): MobilityRepository
    {
        return $this->client->getContainer()->get(MobilityRepository::class);
    }

    public function getWasteRepository(): WasteRepository
    {
        return $this->client->getContainer()->get(WasteRepository::class);
    }

    #[Given('material :name with id :id exists')]
    public function materialWithIdExists(string $name, string $id): void
    {
        $material = MaterialMother::fromNameAndId($name, $id);

        $this->getMaterialRepository()->add($material);
    }

    #[Given('material :name with id :id and emission factor for year :year exists')]
    public function materialWithEmissionFactorExists(string $name, string $id, string $year): void
    {
        $material = MaterialMother::fromNameAndId($name, $id);

        $ef = new EmissionFactor(
            co2eq: '1.5',
            co2: '1.0',
            ch4: '0.2',
            n2o: '0.3',
            year: (int) $year,
            source: 'IPCC',
        );
        $material->setEmissionFactor($ef, $ef);

        $this->getMaterialRepository()->add($material);
    }

    #[Given('merchandising item :name with id :id exists')]
    public function merchandisingItemWithIdExists(string $name, string $id): void
    {
        $merchandisingItem = MerchandisingItemMother::fromNameAndId($name, $id);

        $this->getMerchandisingItemRepository()->add($merchandisingItem);
    }

    #[Given('catering item :name with id :id exists')]
    public function cateringItemWithIdExists(string $name, string $id): void
    {
        $cateringItem = CateringItemMother::fromNameAndId($name, $id);

        $this->getCateringItemRepository()->add($cateringItem);
    }

    #[Given('combustible :name with id :id exists')]
    public function combustibleWithIdExists(string $name, string $id): void
    {
        $combustible = CombustibleMother::fromNameAndId($name, $id);

        $this->getCombustibleRepository()->add($combustible);
    }

    #[Given('combustible :name with id :id and emission factor for year :year exists')]
    public function combustibleWithEmissionFactorExists(string $name, string $id, string $year): void
    {
        $combustible = CombustibleMother::fromNameAndId($name, $id);

        $combustible->setEmissionFactor(new EmissionFactor(
            co2eq: '1.5',
            co2: '1.0',
            ch4: '0.2',
            n2o: '0.3',
            year: (int) $year,
            source: 'IPCC',
        ));

        $this->getCombustibleRepository()->add($combustible);
    }

    #[Given('road transport :name with id :id exists')]
    public function roadTransportWithIdExists(string $name, string $id): void
    {
        $roadTransport = RoadTransportMother::fromNameAndId($name, $id);

        $this->getRoadTransportRepository()->add($roadTransport);
    }

    #[Given('road transport :name with id :id and emission factor for year :year exists')]
    public function roadTransportWithEmissionFactorExists(string $name, string $id, string $year): void
    {
        $roadTransport = RoadTransportMother::fromNameAndId($name, $id);

        $roadTransport->setEmissionFactor(new EmissionFactor(
            co2eq: '1.5',
            co2: '1.0',
            ch4: '0.2',
            n2o: '0.3',
            year: (int) $year,
            source: 'IPCC',
        ));

        $this->getRoadTransportRepository()->add($roadTransport);
    }

    #[Given('electricity marketer :name with id :id exists')]
    public function electricityMarketerWithIdExists(string $name, string $id): void
    {
        $electricityMarketer = ElectricityMarketerMother::fromNameAndId($name, $id);

        $this->getElectricityMarketerRepository()->add($electricityMarketer);
    }

    #[Given('electricity marketer :name with id :id and emission factor for year :year exists')]
    public function electricityMarketerWithEmissionFactorExists(string $name, string $id, string $year): void
    {
        $electricityMarketer = ElectricityMarketerMother::fromNameAndId($name, $id);

        $electricityMarketer->setEmissionFactor(new EmissionFactor(
            co2eq: '1.5',
            co2: '1.0',
            ch4: '0.2',
            n2o: '0.3',
            year: (int) $year,
            source: 'IPCC',
        ));

        $this->getElectricityMarketerRepository()->add($electricityMarketer);
    }

    #[Given('gdo factor :name with id :id exists')]
    public function gdOFactorWithIdExists(string $name, string $id): void
    {
        $gdOFactor = GdOFactorMother::fromNameAndId($name, $id);

        $this->getGdOFactorRepository()->add($gdOFactor);
    }

    #[Given('gdo factor :name with id :id and emission factor for year :year exists')]
    public function gdOFactorWithEmissionFactorExists(string $name, string $id, string $year): void
    {
        $gdOFactor = GdOFactorMother::fromNameAndId($name, $id);

        $gdOFactor->setEmissionFactor(new EmissionFactor(
            co2eq: '1.5',
            co2: '1.0',
            ch4: '0.2',
            n2o: '0.3',
            year: (int) $year,
            source: 'IPCC',
        ));

        $this->getGdOFactorRepository()->add($gdOFactor);
    }

    #[Given('machinery fuel :name with id :id exists')]
    public function machineryFuelWithIdExists(string $name, string $id): void
    {
        $machineryFuel = MachineryFuelMother::fromNameAndId($name, $id);

        $this->getMachineryFuelRepository()->add($machineryFuel);
    }

    #[Given('machinery fuel :name with id :id and emission factor for year :year exists')]
    public function machineryFuelWithEmissionFactorExists(string $name, string $id, string $year): void
    {
        $machineryFuel = MachineryFuelMother::fromNameAndId($name, $id);

        $machineryFuel->setEmissionFactor(new EmissionFactor(
            co2eq: '1.5',
            co2: '1.0',
            ch4: '0.2',
            n2o: '0.3',
            year: (int) $year,
            source: 'IPCC',
        ));

        $this->getMachineryFuelRepository()->add($machineryFuel);
    }

    #[Given('mobility :name with id :id exists')]
    public function mobilityWithIdExists(string $name, string $id): void
    {
        $mobility = MobilityMother::fromNameAndId($name, $id);

        $this->getMobilityRepository()->add($mobility);
    }

    #[Given('mobility :name with id :id and emission factor for year :year exists')]
    public function mobilityWithEmissionFactorExists(string $name, string $id, string $year): void
    {
        $mobility = MobilityMother::fromNameAndId($name, $id);

        $mobility->setEmissionFactor(new EmissionFactor(
            co2eq: '1.5',
            co2: '1.0',
            ch4: '0.2',
            n2o: '0.3',
            year: (int) $year,
            source: 'IPCC',
        ));

        $this->getMobilityRepository()->add($mobility);
    }

    #[Given('waste :name with id :id exists')]
    public function wasteWithIdExists(string $name, string $id): void
    {
        $waste = WasteMother::fromNameAndId($name, $id);

        $this->getWasteRepository()->add($waste);
    }

    #[Given('waste :name with id :id and emission factor for year :year exists')]
    public function wasteWithEmissionFactorExists(string $name, string $id, string $year): void
    {
        $waste = WasteMother::fromNameAndId($name, $id);

        $waste->setEmissionFactor(new EmissionFactor(
            co2eq: '1.5',
            co2: '1.0',
            ch4: '0.2',
            n2o: '0.3',
            year: (int) $year,
            source: 'IPCC',
        ));

        $this->getWasteRepository()->add($waste);
    }
}
