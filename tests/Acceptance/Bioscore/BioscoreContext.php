<?php

declare(strict_types=1);

namespace App\Tests\Acceptance\Bioscore;

use App\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreCatalog;
use App\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreCatalogEntry;
use App\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreCatalogItem;
use App\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreCatalogItemId;
use App\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreCatalogItemRepository;
use App\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreMappingStatus;
use App\Bioscore\Infrastructure\Gateway\InMemoryBioscoreCatalogGateway;
use App\Tests\Acceptance\Shared\AcceptanceClient;
use App\Tests\Unit\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreCatalogItemMother;
use Behat\Behat\Context\Context;
use Behat\Hook\AfterScenario;
use Behat\Step\Given;
use Behat\Step\Then;
use PHPUnit\Framework\Assert;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

final class BioscoreContext implements Context
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

    private function getGateway(): InMemoryBioscoreCatalogGateway
    {
        return $this->client->getContainer()->get(InMemoryBioscoreCatalogGateway::class);
    }

    private function getRepository(): BioscoreCatalogItemRepository
    {
        return $this->client->getContainer()->get(BioscoreCatalogItemRepository::class);
    }

    #[Given('Bioscore catalog :catalog contains item :bioscoreId named :name with unit :unit')]
    public function bioscoreCatalogContainsItem(string $catalog, string $bioscoreId, string $name, string $unit): void
    {
        $this->getGateway()->add(
            BioscoreCatalog::from($catalog),
            new BioscoreCatalogEntry((int) $bioscoreId, $name, $unit),
        );
    }

    #[Given('Bioscore catalog :catalog is unavailable')]
    public function bioscoreCatalogIsUnavailable(string $catalog): void
    {
        $this->getGateway()->fail(BioscoreCatalog::from($catalog));
    }

    #[Given('Bioscore catalog item :bioscoreId of catalog :catalog named :name with id :id exists')]
    public function bioscoreCatalogItemExists(string $bioscoreId, string $catalog, string $name, string $id): void
    {
        $this->getRepository()->add(BioscoreCatalogItemMother::registered(
            catalog: BioscoreCatalog::from($catalog),
            bioscoreId: (int) $bioscoreId,
            name: $name,
            id: $id,
        ));
    }

    #[Given('Bioscore catalog item :id is mapped to :targetId')]
    public function bioscoreCatalogItemIsMappedTo(string $id, string $targetId): void
    {
        $item = $this->item($id);

        $item->map($targetId, automatic: true);

        $this->getRepository()->add($item);
    }

    #[Given('Bioscore catalog item :id is manually mapped to :targetId')]
    public function bioscoreCatalogItemIsManuallyMappedTo(string $id, string $targetId): void
    {
        $item = $this->item($id);

        $item->map($targetId, automatic: false);

        $this->getRepository()->add($item);
    }

    #[Given('Bioscore catalog item :id is ignored')]
    public function bioscoreCatalogItemIsIgnored(string $id): void
    {
        $item = $this->item($id);

        $item->ignore();

        $this->getRepository()->add($item);
    }

    #[Then('Bioscore catalog item :id should be mapped to :targetId')]
    public function bioscoreCatalogItemShouldBeMappedTo(string $id, string $targetId): void
    {
        $item = $this->item($id);

        Assert::assertTrue($item->isMapped());
        Assert::assertSame($targetId, $item->targetId());
    }

    #[Then('Bioscore catalog item :id should be unmapped')]
    public function bioscoreCatalogItemShouldBeUnmapped(string $id): void
    {
        Assert::assertSame(BioscoreMappingStatus::Unmapped, $this->item($id)->status());
    }

    #[Then('Bioscore catalog item :id should be ignored')]
    public function bioscoreCatalogItemShouldBeIgnored(string $id): void
    {
        Assert::assertTrue($this->item($id)->isIgnored());
    }

    #[Then('Bioscore catalog item :id should be withdrawn')]
    public function bioscoreCatalogItemShouldBeWithdrawn(string $id): void
    {
        Assert::assertTrue($this->item($id)->isWithdrawn());
    }

    #[Then('Bioscore catalog item :bioscoreId of catalog :catalog should exist')]
    public function bioscoreCatalogItemOfCatalogShouldExist(string $bioscoreId, string $catalog): void
    {
        Assert::assertNotNull(
            $this->getRepository()->findByCatalogAndBioscoreId(BioscoreCatalog::from($catalog), (int) $bioscoreId),
        );
    }

    private function item(string $id): BioscoreCatalogItem
    {
        $item = $this->getRepository()->find(new BioscoreCatalogItemId($id));

        Assert::assertNotNull($item, sprintf('Bioscore catalog item "%s" not found', $id));

        return $item;
    }
}
