<?php

declare(strict_types=1);

namespace App\Tests\Acceptance\CarbonFootprint;

use App\CarbonFootprint\Domain\Model\CateringItem\CateringItemRepository;
use App\CarbonFootprint\Domain\Model\Material\MaterialRepository;
use App\CarbonFootprint\Domain\Model\MerchandisingItem\MerchandisingItemRepository;
use App\Tests\Acceptance\Shared\AcceptanceClient;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CateringItem\CateringItemMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Material\MaterialMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\MerchandisingItem\MerchandisingItemMother;
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

    #[Given('material :name with id :id exists')]
    public function materialWithIdExists(string $name, string $id): void
    {
        $material = MaterialMother::fromNameAndId($name, $id);

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
}
