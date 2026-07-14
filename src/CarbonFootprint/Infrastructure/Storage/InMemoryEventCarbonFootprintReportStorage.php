<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Storage;

use App\CarbonFootprint\Domain\Model\EventCarbonFootprintReport\EventCarbonFootprintReportId;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprintReport\EventCarbonFootprintReportStorage;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Model\Locale\Locale;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class InMemoryEventCarbonFootprintReportStorage implements EventCarbonFootprintReportStorage
{
    /** @var array<string, string> */
    private array $contents = [];

    /** @var list<string> */
    private array $deletedPaths = [];

    public function store(EventCarbonFootprintReportId $id, ?AccountId $accountId, Locale $locale, string $localPath): string
    {
        $scope = $accountId instanceof AccountId ? (string) $accountId : 'shared';
        $path = sprintf('%s/%s-%s.pdf', $scope, (string) $id, $locale->value);

        $this->contents[$path] = (string) file_get_contents($localPath);

        return $path;
    }

    public function read(string $path): mixed
    {
        $stream = fopen('php://memory', 'r+b');
        assert(false !== $stream);

        fwrite($stream, $this->contents[$path] ?? '');
        rewind($stream);

        return $stream;
    }

    public function delete(?string $path): void
    {
        if (null === $path) {
            return;
        }

        unset($this->contents[$path]);
        $this->deletedPaths[] = $path;
    }

    /** @return list<string> */
    public function paths(): array
    {
        return array_keys($this->contents);
    }

    /** @return list<string> */
    public function deleted(): array
    {
        return $this->deletedPaths;
    }
}
