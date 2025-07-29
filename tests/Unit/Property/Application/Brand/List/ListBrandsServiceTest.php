<?php

namespace App\Tests\Unit\Property\Application\Brand\List;

use PHPUnit\Framework\TestCase;
use App\Security\Domain\Model\User\Role;
use App\Property\Application\Brand\BrandAuthorizer;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Property\Domain\Model\Brand\BrandMother;
use App\Property\Application\Brand\List\ListBrandsRequest;
use App\Property\Application\Brand\List\ListBrandsService;
use App\Property\Application\Brand\List\ListBrandsResponse;
use App\Tests\Unit\Property\Domain\Model\Brand\BrandRepositoryMother;

class ListBrandsServiceTest extends TestCase
{
    protected function setUp(): void
    {
        
    }

    public function testInvoke()
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

    private function createSUT($repository)
    {
        return new ListBrandsService($repository, new BrandAuthorizer());
    }
}