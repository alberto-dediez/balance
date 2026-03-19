<?php

namespace App\Tests\Unit\Property\Application\Country\List;

use App\Property\Infrastructure\Persistence\InMemory\Repository\InMemoryCountryRepository;
use PHPUnit\Framework\TestCase;
use App\Security\Domain\Model\User\Role;
use App\Property\Application\Country\CountryAuthorizer;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Property\Domain\Model\Country\CountryMother;
use App\Property\Application\Country\List\ListCountriesRequest;
use App\Property\Application\Country\List\ListCountriesService;
use App\Property\Application\Country\List\ListCountriesResponse;
use App\Tests\Unit\Property\Domain\Model\Country\CountryRepositoryMother;

class ListCountriesServiceTest extends TestCase
{
    protected function setUp(): void
    {
        
    }

    public function testInvoke(): void
    {
        $repository = CountryRepositoryMother::inMemory();

        $user = UserMother::fromUsernameAndRole('admin', Role::Admin);

        $sut = $this->createSUT($repository);

        $repository->add(CountryMother::random());
        $repository->add(CountryMother::random());
        $repository->add(CountryMother::random());
        $repository->add(CountryMother::random());

        $request = new ListCountriesRequest();

        $result = $sut($request, $user);

        $this->assertNotNull($result);
        $this->assertTrue($result instanceof ListCountriesResponse);
        $this->assertCount(4, $result->countries);
        $this->assertEquals(4, $result->totalResults);
    }

    private function createSUT(InMemoryCountryRepository $repository): ListCountriesService
    {
        return new ListCountriesService($repository, new CountryAuthorizer());
    }
}