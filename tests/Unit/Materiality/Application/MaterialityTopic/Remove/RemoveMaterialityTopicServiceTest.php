<?php

declare(strict_types=1);

namespace App\Tests\Unit\Materiality\Application\MaterialityTopic\Remove;

use App\Materiality\Application\MaterialityTopic\MaterialityTopicAuthorizer;
use App\Materiality\Application\MaterialityTopic\Remove\RemoveMaterialityTopicRequest;
use App\Materiality\Application\MaterialityTopic\Remove\RemoveMaterialityTopicService;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicRemoved;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicRepository;
use App\Materiality\Domain\Model\MaterialityTopic\StandardScope3Category;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicMother;
use App\Tests\Unit\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class RemoveMaterialityTopicServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testRemovesOwnTopic(): void
    {
        $repository = MaterialityTopicRepositoryMother::inMemory();
        $materialityTopic = MaterialityTopicMother::random();
        $repository->add($materialityTopic);

        $this->createSUT($repository)(new RemoveMaterialityTopicRequest($materialityTopic->id()), UserMother::adminUser());

        $this->assertNull($repository->find($materialityTopic->id()));
        $this->assertInstanceOf(MaterialityTopicRemoved::class, $this->spySubscriber->lastEvent());
    }

    public function testStandardTopicCannotBeRemoved(): void
    {
        $repository = MaterialityTopicRepositoryMother::inMemory();
        $materialityTopic = MaterialityTopicMother::standard(StandardScope3Category::Investments);
        $repository->add($materialityTopic);

        $this->expectException(UnauthorizedActionException::class);

        $this->createSUT($repository)(new RemoveMaterialityTopicRequest($materialityTopic->id()), UserMother::adminUser());
    }

    public function testOwnerCannotRemove(): void
    {
        $repository = MaterialityTopicRepositoryMother::inMemory();
        $materialityTopic = MaterialityTopicMother::random();
        $repository->add($materialityTopic);

        $this->expectException(UnauthorizedActionException::class);

        $this->createSUT($repository)(
            new RemoveMaterialityTopicRequest($materialityTopic->id()),
            UserMother::fromUsernameAndRole('owner', Role::Owner),
        );
    }

    private function createSUT(MaterialityTopicRepository $repository): RemoveMaterialityTopicService
    {
        return new RemoveMaterialityTopicService(
            materialityTopicRepository: $repository,
            authorizer: new MaterialityTopicAuthorizer(),
        );
    }
}
