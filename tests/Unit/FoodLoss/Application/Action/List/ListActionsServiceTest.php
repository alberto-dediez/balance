<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Application\Action\List;

use App\FoodLoss\Application\Action\ActionAuthorizer;
use App\FoodLoss\Application\Action\List\ListActionsRequest;
use App\FoodLoss\Application\Action\List\ListActionsService;
use App\FoodLoss\Domain\Model\Action\ActionRepository;
use App\Tests\Unit\FoodLoss\Domain\Model\Action\ActionMother;
use App\Tests\Unit\FoodLoss\Domain\Model\Action\ActionRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class ListActionsServiceTest extends TestCase
{
    protected function setUp(): void {}

    public function testInvoke(): void
    {
        $repository = ActionRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $repository->add(ActionMother::random());
        $repository->add(ActionMother::random());
        $repository->add(ActionMother::random());
        $repository->add(ActionMother::random());

        $request = new ListActionsRequest();

        $result = $sut($request, UserMother::adminUser());

        $this->assertCount(4, $result->actions);
        $this->assertEquals(4, $result->totalResults);
    }

    public function testFilterByDescription(): void
    {
        $repository = ActionRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $repository->add(ActionMother::fromDescription('description-1'));
        $repository->add(ActionMother::fromDescription('description-2'));
        $repository->add(ActionMother::fromDescription('description-2'));
        $repository->add(ActionMother::fromDescription('description-2'));

        $request = new ListActionsRequest(
            description: 'description-1',
        );

        $result = $sut($request, UserMother::adminUser());

        $this->assertCount(1, $result->actions);
        $this->assertEquals(1, $result->totalResults);

        $request = new ListActionsRequest(
            description: 'description-2',
        );

        $result = $sut($request, UserMother::adminUser());

        $this->assertCount(3, $result->actions);
        $this->assertEquals(3, $result->totalResults);

        $request = new ListActionsRequest();

        $result = $sut($request, UserMother::adminUser());

        $this->assertCount(4, $result->actions);
        $this->assertEquals(4, $result->totalResults);
    }

    private function createSUT(ActionRepository $repository): ListActionsService
    {
        return new ListActionsService($repository, new ActionAuthorizer());
    }
}
