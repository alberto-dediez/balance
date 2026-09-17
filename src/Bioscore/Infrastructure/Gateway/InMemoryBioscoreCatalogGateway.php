<?php

declare(strict_types=1);

namespace App\Bioscore\Infrastructure\Gateway;

use App\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreCatalog;
use App\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreCatalogEntry;
use App\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreCatalogGateway;
use App\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreUnavailableException;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
final class InMemoryBioscoreCatalogGateway implements BioscoreCatalogGateway
{
    /** @var array<string, list<BioscoreCatalogEntry>> */
    private array $entries = [];

    /** @var list<string> */
    private array $failed = [];

    /** @param list<BioscoreCatalogEntry> $entries */
    public function set(BioscoreCatalog $catalog, array $entries): void
    {
        $this->entries[$catalog->value] = $entries;
    }

    public function add(BioscoreCatalog $catalog, BioscoreCatalogEntry $entry): void
    {
        $this->entries[$catalog->value][] = $entry;
    }

    public function fail(BioscoreCatalog $catalog): void
    {
        $this->failed[] = $catalog->value;
    }

    public function failAll(): void
    {
        foreach (BioscoreCatalog::cases() as $catalog) {
            $this->fail($catalog);
        }
    }

    public function reset(): void
    {
        $this->entries = [];
        $this->failed = [];
    }

    public function fetch(BioscoreCatalog $catalog): array
    {
        if (in_array($catalog->value, $this->failed, true)) {
            throw BioscoreUnavailableException::fromCatalog($catalog);
        }

        return $this->entries[$catalog->value] ?? [];
    }
}
