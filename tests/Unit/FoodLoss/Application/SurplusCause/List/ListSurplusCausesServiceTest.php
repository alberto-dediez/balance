<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Application\SurplusCause\List;

use App\FoodLoss\Application\SurplusCause\List\ListSurplusCausesRequest;
use App\FoodLoss\Application\SurplusCause\List\ListSurplusCausesService;
use App\FoodLoss\Application\SurplusCause\SurplusCauseAuthorizer;
use App\FoodLoss\Domain\Model\SurplusCause\SurplusCauseRepository;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\FoodLoss\Domain\Model\SurplusCause\SurplusCauseMother;
use App\Tests\Unit\FoodLoss\Domain\Model\SurplusCause\SurplusCauseRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use PHPUnit\Framework\TestCase;

class ListSurplusCausesServiceTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        FakeLocaleSetter::reset();
    }

    public function testUseCase(): void
    {
        $repository = SurplusCauseRepositoryMother::inMemory();

        $repository->add(SurplusCauseMother::random());
        $repository->add(SurplusCauseMother::random());
        $repository->add(SurplusCauseMother::random());

        $sut = $this->createSUT($repository);

        $result = $sut(new ListSurplusCausesRequest(), UserMother::adminUser());

        $this->assertCount(3, $result->surplusCauses);
        $this->assertEquals(3, $result->totalResults);
    }

    private function createSUT(SurplusCauseRepository $repository): ListSurplusCausesService
    {
        return new ListSurplusCausesService(
            repository: $repository,
            authorizer: new SurplusCauseAuthorizer(),
        );
    }
}
