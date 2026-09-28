<?php

declare(strict_types=1);

namespace App\Tests\Unit\Materiality\Application\MaterialityTopic\List;

use App\Materiality\Application\MaterialityTopic\List\ListMaterialityTopicsRequest;
use App\Materiality\Application\MaterialityTopic\List\ListMaterialityTopicsService;
use App\Materiality\Application\MaterialityTopic\MaterialityTopicAuthorizer;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\User\Role;
use App\Tests\Unit\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicMother;
use App\Tests\Unit\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class ListMaterialityTopicsServiceTest extends TestCase
{
    public function testReturnsPageAndTotal(): void
    {
        $repository = MaterialityTopicRepositoryMother::inMemory();

        for ($i = 0; $i < 3; ++$i) {
            $repository->add(MaterialityTopicMother::random());
        }

        $request = new ListMaterialityTopicsRequest(limit: 2);

        $response = (new ListMaterialityTopicsService($repository, new MaterialityTopicAuthorizer()))($request, UserMother::adminUser());

        $this->assertCount(2, $response->materialityTopics);
        $this->assertSame(3, $response->totalResults);
    }

    public function testOwnerCannotList(): void
    {
        $this->expectException(UnauthorizedActionException::class);

        (new ListMaterialityTopicsService(MaterialityTopicRepositoryMother::inMemory(), new MaterialityTopicAuthorizer()))(
            new ListMaterialityTopicsRequest(),
            UserMother::fromUsernameAndRole('owner', Role::Owner),
        );
    }
}
