<?php

declare(strict_types=1);

namespace App\Tests\Unit\Materiality\Domain\Event;

use App\Materiality\Application\MaterialityQuestion\MaterialityQuestionAuthorizer;
use App\Materiality\Application\MaterialityQuestion\Remove\RemoveMaterialityQuestionService;
use App\Materiality\Domain\Event\RemoveTopicQuestionsOnMaterialityTopicRemovedSubscriber;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicId;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicRemoved;
use App\Materiality\Infrastructure\Persistence\InMemory\Repository\InMemoryMaterialityQuestionRepository;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Materiality\Domain\Model\MaterialityQuestion\MaterialityQuestionMother;
use App\Tests\Unit\Materiality\Domain\Model\MaterialityQuestion\MaterialityQuestionRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Security\Infrastructure\Symfony\Security\FakeLoggedUser;
use PHPUnit\Framework\TestCase;

class RemoveTopicQuestionsOnMaterialityTopicRemovedSubscriberTest extends TestCase
{
    private InMemoryMaterialityQuestionRepository $repository;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->repository = MaterialityQuestionRepositoryMother::inMemory();
    }

    public function testIsSubscribedToMaterialityTopicRemoved(): void
    {
        $this->assertTrue($this->createSUT()->isSubscribedTo(new MaterialityTopicRemoved(MaterialityTopicId::generate())));
    }

    public function testRemovesOnlyTheQuestionsOfTheRemovedTopic(): void
    {
        $topicId = MaterialityTopicId::generate();

        $first = MaterialityQuestionMother::forTopic($topicId);
        $second = MaterialityQuestionMother::forTopic($topicId);
        $generic = MaterialityQuestionMother::random();
        $other = MaterialityQuestionMother::forTopic(MaterialityTopicId::generate());

        foreach ([$first, $second, $generic, $other] as $materialityQuestion) {
            $this->repository->add($materialityQuestion);
        }

        $this->createSUT()->handle(new MaterialityTopicRemoved($topicId));

        $this->assertTrue($first->isRemoved());
        $this->assertTrue($second->isRemoved());
        $this->assertFalse($generic->isRemoved());
        $this->assertFalse($other->isRemoved());
    }

    private function createSUT(): RemoveTopicQuestionsOnMaterialityTopicRemovedSubscriber
    {
        return new RemoveTopicQuestionsOnMaterialityTopicRemovedSubscriber(
            $this->repository,
            new RemoveMaterialityQuestionService($this->repository, new MaterialityQuestionAuthorizer()),
            new FakeLoggedUser(UserMother::adminUser()),
        );
    }
}
