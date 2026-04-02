<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Application\Reason\List;

use App\FoodLoss\Application\Reason\List\ListReasonsRequest;
use App\FoodLoss\Application\Reason\List\ListReasonsService;
use App\FoodLoss\Application\Reason\ReasonAuthorizer;
use App\FoodLoss\Domain\Model\Reason\ReasonRepository;
use App\Tests\Unit\FoodLoss\Domain\Model\Reason\ReasonMother;
use App\Tests\Unit\FoodLoss\Domain\Model\Reason\ReasonRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use PHPUnit\Framework\TestCase;

class ListReasonsServiceTest extends TestCase
{
    protected function setUp(): void
    {
        FakeLocaleSetter::reset();
    }

    public function testUseCase(): void
    {
        $repository = ReasonRepositoryMother::inMemory();

        $repository->add(ReasonMother::random());
        $repository->add(ReasonMother::random());
        $repository->add(ReasonMother::random());

        $sut = $this->createSUT($repository);

        $result = $sut(new ListReasonsRequest(), UserMother::adminUser());

        $this->assertCount(3, $result->reasons);
        $this->assertEquals(3, $result->totalResults);
    }

    private function createSUT(ReasonRepository $repository): ListReasonsService
    {
        return new ListReasonsService(
            repository: $repository,
            authorizer: new ReasonAuthorizer(),
        );
    }
}
