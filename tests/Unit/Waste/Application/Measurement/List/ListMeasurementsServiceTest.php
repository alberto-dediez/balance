<?php

declare(strict_types=1);

namespace App\Tests\Unit\Waste\Application\Measurement\List;

use App\Security\Domain\Model\Account\Account;
use App\Security\Domain\Model\Account\AccountId;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Waste\Domain\Model\Measurement\MeasurementMother;
use App\Tests\Unit\Waste\Domain\Model\Measurement\MeasurementRepositoryMother;
use App\Waste\Application\Measurement\List\ListMeasurementsRequest;
use App\Waste\Application\Measurement\List\ListMeasurementsResponse;
use App\Waste\Application\Measurement\List\ListMeasurementsService;
use App\Waste\Application\Measurement\MeasurementAuthorizer;
use App\Waste\Infrastructure\Persistence\InMemory\Repository\InMemoryMeasurementRepository;
use PHPUnit\Framework\TestCase;

class ListMeasurementsServiceTest extends TestCase
{
    protected function setUp(): void {}

    public function testInvoke(): void
    {
        $repository = MeasurementRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $account = new Account(AccountId::generate());
        $user = UserMother::fromUsernameAndAccount('test', $account);

        $repository->add(MeasurementMother::fromAccount($account->id()));
        $repository->add(MeasurementMother::fromAccount($account->id()));
        $repository->add(MeasurementMother::fromAccount($account->id()));
        $repository->add(MeasurementMother::fromAccount($account->id()));

        $request = new ListMeasurementsRequest(
            accountId: $account->id(),
        );

        $result = $sut($request, $user);

        $this->assertNotNull($result);
        $this->assertTrue($result instanceof ListMeasurementsResponse);
        $this->assertCount(4, $result->measurements);
        $this->assertEquals(4, $result->totalResults);
    }

    private function createSUT(InMemoryMeasurementRepository $repository): ListMeasurementsService
    {
        return new ListMeasurementsService($repository, new MeasurementAuthorizer());
    }
}
