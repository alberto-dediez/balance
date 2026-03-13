<?php

namespace App\Tests\Unit\FoodLoss\Application\Action\List;

use PHPUnit\Framework\TestCase;
use App\FoodLoss\Application\Action\ActionAuthorizer;
use App\FoodLoss\Application\Action\List\ListActionsRequest;
use App\FoodLoss\Application\Action\List\ListActionsService;
use App\Tests\Unit\FoodLoss\Domain\Model\Action\ActionMother;
use App\FoodLoss\Application\Action\List\ListActionsResponse;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\FoodLoss\Domain\Model\Action\ActionRepositoryMother;

class ListActionsServiceTest extends TestCase
{
    protected function setUp(): void
    {
        
    }

    public function testInvoke()
    {
        $repository = ActionRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $repository->add(ActionMother::random());
        $repository->add(ActionMother::random());
        $repository->add(ActionMother::random());
        $repository->add(ActionMother::random());

        $request = new ListActionsRequest();

        $result = $sut($request, UserMother::adminUser());

        $this->assertNotNull($result);
        $this->assertTrue($result instanceof ListActionsResponse);
        $this->assertCount(4, $result->actions);
        $this->assertEquals(4, $result->totalResults);
    }

    public function testFilterByDescription()
    {
        $repository = ActionRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $repository->add(ActionMother::fromDescription('description-1'));
        $repository->add(ActionMother::fromDescription('description-2'));
        $repository->add(ActionMother::fromDescription('description-2'));
        $repository->add(ActionMother::fromDescription('description-2'));

        $request = new ListActionsRequest(
            description: 'description-1'
        );

        $result = $sut($request, UserMother::adminUser());

        $this->assertNotNull($result);
        $this->assertTrue($result instanceof ListActionsResponse);
        $this->assertCount(1, $result->actions);
        $this->assertEquals(1, $result->totalResults);

        $request = new ListActionsRequest(
            description: 'description-2'
        );

        $result = $sut($request, UserMother::adminUser());

        $this->assertNotNull($result);
        $this->assertTrue($result instanceof ListActionsResponse);
        $this->assertCount(3, $result->actions);
        $this->assertEquals(3, $result->totalResults);

        $request = new ListActionsRequest(
            description: null
        );

        $result = $sut($request, UserMother::adminUser());

        $this->assertNotNull($result);
        $this->assertTrue($result instanceof ListActionsResponse);
        $this->assertCount(4, $result->actions);
        $this->assertEquals(4, $result->totalResults);
    }

    private function createSUT($repository)
    {
        return new ListActionsService($repository, new ActionAuthorizer());
    }
}