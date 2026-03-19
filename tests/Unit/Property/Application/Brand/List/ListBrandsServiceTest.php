<?php

declare(strict_types=1);

namespace App\Tests\Unit\Property\Application\Brand\List;

use App\Property\Application\Brand\BrandAuthorizer;
use App\Property\Application\Brand\List\ListBrandsRequest;
use App\Property\Application\Brand\List\ListBrandsResponse;
use App\Property\Application\Brand\List\ListBrandsService;
use App\Property\Infrastructure\Persistence\InMemory\Repository\InMemoryBrandRepository;
use App\Security\Domain\Model\User\Role;
use App\Tests\Unit\Property\Domain\Model\Brand\BrandMother;
use App\Tests\Unit\Property\Domain\Model\Brand\BrandRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class ListBrandsServiceTest extends TestCase
{
    protected function setUp(): void {}

    public function testInvoke(): void
    {
        $repository = BrandRepositoryMother::inMemory();

        $user = UserMother::fromUsernameAndRole('admin', Role::Admin);

        $sut = $this->createSUT($repository);

        $repository->add(BrandMother::random());
        $repository->add(BrandMother::random());
        $repository->add(BrandMother::random());
        $repository->add(BrandMother::random());

        $request = new ListBrandsRequest();

        $result = $sut($request, $user);

        $this->assertNotNull($result);
        $this->assertTrue($result instanceof ListBrandsResponse);
        $this->assertCount(4, $result->brands);
        $this->assertEquals(4, $result->totalResults);
    }

    private function createSUT(InMemoryBrandRepository $repository): ListBrandsService
    {
        return new ListBrandsService($repository, new BrandAuthorizer());
    }
}
