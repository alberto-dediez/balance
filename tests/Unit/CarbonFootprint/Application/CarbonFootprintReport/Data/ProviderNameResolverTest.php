<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\CarbonFootprintReport\Data;

use App\CarbonFootprint\Application\CarbonFootprintReport\Data\ProviderNameResolver;
use App\EventOperations\Domain\Model\Provider\ProviderId;
use App\Tests\Unit\EventOperations\Domain\Model\Provider\ProviderMother;
use App\Tests\Unit\EventOperations\Domain\Model\Provider\ProviderRepositoryMother;
use PHPUnit\Framework\TestCase;

final class ProviderNameResolverTest extends TestCase
{
    public function testKeepsTheResolvedNameUntilItIsReset(): void
    {
        $providers = ProviderRepositoryMother::inMemory();
        $providerId = (string) ProviderId::generate();
        $provider = ProviderMother::fromNameAndId('Logística ACME', $providerId);
        $providers->add($provider);
        $sut = new ProviderNameResolver($providers);

        $resolved = $sut->resolve(new ProviderId($providerId));

        $providers->remove($provider);
        $providers->add(ProviderMother::fromNameAndId('Transportes Norte', $providerId));

        $cached = $sut->resolve(new ProviderId($providerId));
        $sut->reset();
        $current = $sut->resolve(new ProviderId($providerId));

        $this->assertSame('Logística ACME', $resolved);
        $this->assertSame('Logística ACME', $cached);
        $this->assertSame('Transportes Norte', $current);
    }
}
