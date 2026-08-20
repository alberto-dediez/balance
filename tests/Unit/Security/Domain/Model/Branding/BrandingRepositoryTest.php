<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Domain\Model\Branding;

use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\Branding\BrandingFilter;
use App\Security\Domain\Model\Branding\BrandingRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class BrandingRepositoryTest extends KernelTestCase
{
    public function testAddBrandingToRepository(): void
    {
        $repository = $this->createSUT();

        $branding = BrandingMother::random();

        $this->assertNull($repository->find($branding->id()));

        $repository->add($branding);

        $recovered = $repository->find($branding->id());

        $this->assertNotNull($recovered);
        $this->assertEquals($branding->id(), $recovered->id());
    }

    public function testRemoveBrandingFromRepository(): void
    {
        $repository = $this->createSUT();

        $branding = BrandingMother::random();

        $repository->add($branding);

        $this->assertNotNull($repository->find($branding->id()));

        $repository->remove($branding);

        $this->assertNull($repository->find($branding->id()));
    }

    public function testFindByAccountId(): void
    {
        $repository = $this->createSUT();

        $accountId = AccountId::generate();
        $branding = BrandingMother::forAccount($accountId);

        $repository->add($branding);

        $recovered = $repository->findByAccountId($accountId);

        $this->assertNotNull($recovered);
        $this->assertEquals($branding->id(), $recovered->id());
    }

    public function testFindByAccountIdReturnsNullForUnknownAccount(): void
    {
        $repository = $this->createSUT();

        $repository->add(BrandingMother::random());

        $this->assertNull($repository->findByAccountId(AccountId::generate()));
    }

    public function testRemovedBrandingIsNotFound(): void
    {
        $repository = $this->createSUT();

        $accountId = AccountId::generate();
        $branding = BrandingMother::forAccount($accountId);

        $repository->add($branding);

        $branding->remove();
        $repository->add($branding);

        $this->assertNull($repository->find($branding->id()));
        $this->assertNull($repository->findByAccountId($accountId));
    }

    public function testFindByFilterFiltersByThemeSlug(): void
    {
        $repository = $this->createSUT();

        $themed = BrandingMother::random();
        $themed->changeTheme('aurora');

        $repository->add(BrandingMother::random());
        $repository->add($themed);

        $results = $repository->findByFilter(new BrandingFilter(themeSlug: 'aurora'));

        $this->assertCount(1, $results);
        $this->assertEquals($themed->id(), $results[0]->id());
        $this->assertSame(1, $repository->totalResultsByFilter(new BrandingFilter(themeSlug: 'aurora')));
    }

    public function testTotalResultsIgnoresPagination(): void
    {
        $repository = $this->createSUT();

        for ($i = 0; $i < 3; ++$i) {
            $repository->add(BrandingMother::random());
        }

        $this->assertSame(3, $repository->totalResultsByFilter(new BrandingFilter(limit: 2)));
    }

    abstract public function createSUT(): BrandingRepository;
}
