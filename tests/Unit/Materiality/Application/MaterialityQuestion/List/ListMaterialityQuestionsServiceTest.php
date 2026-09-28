<?php

declare(strict_types=1);

namespace App\Tests\Unit\Materiality\Application\MaterialityQuestion\List;

use App\Materiality\Application\MaterialityQuestion\List\ListMaterialityQuestionsRequest;
use App\Materiality\Application\MaterialityQuestion\List\ListMaterialityQuestionsService;
use App\Materiality\Application\MaterialityQuestion\MaterialityQuestionAuthorizer;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\User\Role;
use App\Tests\Unit\Materiality\Domain\Model\MaterialityQuestion\MaterialityQuestionMother;
use App\Tests\Unit\Materiality\Domain\Model\MaterialityQuestion\MaterialityQuestionRepositoryMother;
use App\Tests\Unit\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicMother;
use App\Tests\Unit\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class ListMaterialityQuestionsServiceTest extends TestCase
{
    public function testReturnsPageTotalAndReferencedTopics(): void
    {
        $topicRepository = MaterialityTopicRepositoryMother::inMemory();
        $referenced = MaterialityTopicMother::random();
        $notReferenced = MaterialityTopicMother::random();
        $topicRepository->add($referenced);
        $topicRepository->add($notReferenced);

        $repository = MaterialityQuestionRepositoryMother::inMemory();
        $repository->add(MaterialityQuestionMother::forTopic($referenced->id()));
        $repository->add(MaterialityQuestionMother::random());
        $repository->add(MaterialityQuestionMother::random());

        $response = (new ListMaterialityQuestionsService($repository, $topicRepository, new MaterialityQuestionAuthorizer()))(
            new ListMaterialityQuestionsRequest(),
            UserMother::adminUser(),
        );

        $this->assertCount(3, $response->materialityQuestions);
        $this->assertSame(3, $response->totalResults);
        $this->assertSame([(string) $referenced->id()], array_keys($response->topicsById));
    }

    public function testOwnerCannotList(): void
    {
        $this->expectException(UnauthorizedActionException::class);

        (new ListMaterialityQuestionsService(
            MaterialityQuestionRepositoryMother::inMemory(),
            MaterialityTopicRepositoryMother::inMemory(),
            new MaterialityQuestionAuthorizer(),
        ))(
            new ListMaterialityQuestionsRequest(),
            UserMother::fromUsernameAndRole('owner', Role::Owner),
        );
    }
}
