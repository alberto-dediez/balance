<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\OvernightStay\List;

use App\CarbonFootprint\Application\OvernightStay\List\ListOvernightStaysRequest;
use App\CarbonFootprint\Application\OvernightStay\List\ListOvernightStaysService;
use App\CarbonFootprint\Application\OvernightStay\OvernightStayAuthorizer;
use App\CarbonFootprint\Domain\Model\OvernightStay\OvernightStayRepository;
use App\Tests\Unit\CarbonFootprint\Domain\Model\OvernightStay\OvernightStayMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\OvernightStay\OvernightStayRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class ListOvernightStaysServiceTest extends TestCase
{
    protected function setUp(): void {}

    public function testInvoke(): void
    {
        $repository = OvernightStayRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $repository->add(OvernightStayMother::random());
        $repository->add(OvernightStayMother::random());
        $repository->add(OvernightStayMother::random());
        $repository->add(OvernightStayMother::random());

        $request = new ListOvernightStaysRequest();

        $result = $sut($request, UserMother::adminUser());

        $this->assertCount(4, $result->overnightStays);
        $this->assertEquals(4, $result->totalResults);
    }

    public function testFilterByName(): void
    {
        $repository = OvernightStayRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $repository->add(OvernightStayMother::fromName('name-1'));
        $repository->add(OvernightStayMother::fromName('name-2'));
        $repository->add(OvernightStayMother::fromName('name-2'));
        $repository->add(OvernightStayMother::fromName('name-2'));

        $request = new ListOvernightStaysRequest(name: 'name-1');

        $result = $sut($request, UserMother::adminUser());

        $this->assertCount(1, $result->overnightStays);
        $this->assertEquals(1, $result->totalResults);

        $request = new ListOvernightStaysRequest(name: 'name-2');

        $result = $sut($request, UserMother::adminUser());

        $this->assertCount(3, $result->overnightStays);
        $this->assertEquals(3, $result->totalResults);
    }

    private function createSUT(OvernightStayRepository $repository): ListOvernightStaysService
    {
        return new ListOvernightStaysService($repository, new OvernightStayAuthorizer());
    }
}
