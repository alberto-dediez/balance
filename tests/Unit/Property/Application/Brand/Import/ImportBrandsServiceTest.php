<?php

namespace App\Tests\Unit\Property\Application\Brand\Import;

use App\Property\Infrastructure\Persistence\InMemory\Repository\InMemoryBrandRepository;
use PHPUnit\Framework\TestCase;
use App\Security\Domain\Model\User\Role;
use App\Property\Domain\Model\Brand\BrandId;
use App\Property\Domain\Model\Property\ExternalId;
use App\Property\Application\Brand\BrandAuthorizer;
use App\Property\Domain\Model\Property\ExternalSource;
use App\Property\Application\Brand\Import\BrandRequest;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Property\Domain\Model\Brand\BrandMother;
use App\Property\Application\Brand\Import\ImportBrandsRequest;
use App\Property\Application\Brand\Import\ImportBrandsService;
use App\Property\Application\Brand\Import\ImportBrandsResponse;
use App\Property\Application\Brand\Register\RegisterBrandService;
use App\Property\Application\Brand\Register\RegisterBrandResponse;
use App\Property\Application\Brand\Update\UpdateBrandService;
use App\Tests\Unit\Property\Domain\Model\Brand\BrandRepositoryMother;

class ImportBrandsServiceTest extends TestCase
{
    protected function setUp(): void
    {
        
    }

    public function testInvoke(): void
    {
        $repository = BrandRepositoryMother::inMemory();

        $user = UserMother::fromUsernameAndRole('admin', Role::Admin);

        $sut = $this->createSUT($repository);

        $ibis = BrandMother::fromName('ibis');
        $orizonia = BrandMother::fromName('orizonia');
        $mcdonalds = BrandMother::fromName('mcdonalds');

        $repository->add($ibis);
        $repository->add($mcdonalds);
        $repository->add($orizonia);
        $repository->add(BrandMother::random());
        $repository->add(BrandMother::random());
        $repository->add(BrandMother::random());

        $request = new ImportBrandsRequest(
            [
                new BrandRequest(
                    name: 'ibis',
                    source: ExternalSource::Bioscore,
                    externalId: ExternalId::fromSourceAndId(ExternalSource::Bioscore, '1234'),
                    createIfNotExists: false
                ),
                new BrandRequest(
                    name: 'Orizonia',
                    source: ExternalSource::Bioscore,
                    externalId: ExternalId::fromSourceAndId(ExternalSource::Bioscore, '234'),
                    createIfNotExists: false
                ),
                new BrandRequest(
                    name: 'Burger King',
                    source: ExternalSource::Bioscore,
                    externalId: ExternalId::fromSourceAndId(ExternalSource::Bioscore, '34'),
                    createIfNotExists: false
                ),
                new BrandRequest(
                    name: 'Travelplan',
                    source: ExternalSource::Bioscore,
                    externalId: ExternalId::fromSourceAndId(ExternalSource::Bioscore, '4'),
                    createIfNotExists: true
                ),
            ]
        );

        $result = $sut($request, $user);

        $this->assertNotNull($result);
        $this->assertTrue($result instanceof ImportBrandsResponse);

        $this->assertCount(4, $result->brands);
        $this->assertEquals(3, $result->matched);

        $this->assertNotNull($result->brands[0]->brandId);
        $this->assertNotNull($result->brands[1]->brandId);
        $this->assertNull($result->brands[2]->brandId);
        $this->assertNotNull($result->brands[3]->brandId);

        $this->assertEquals('1234', $ibis->externalId()->bioscore());
    }

    private function createSUT(InMemoryBrandRepository $repository): ImportBrandsService
    {
        $registerService = $this->createMock(RegisterBrandService::class);
        $registerService->method('__invoke')->willReturn(new RegisterBrandResponse(BrandId::generate(), '', null));

        /** @var RegisterBrandService $registerService */

        $authorizer = new BrandAuthorizer();

        $updateService = new UpdateBrandService(
            $repository, $authorizer
        );

        return new ImportBrandsService(
            repository: $repository, 
            registerBrand: $registerService,
            updateBrand: $updateService,
            authorizer: $authorizer
        );
    }
}