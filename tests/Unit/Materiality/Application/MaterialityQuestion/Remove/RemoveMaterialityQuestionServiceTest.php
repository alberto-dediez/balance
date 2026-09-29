<?php

declare(strict_types=1);

namespace App\Tests\Unit\Materiality\Application\MaterialityQuestion\Remove;

use App\Materiality\Application\MaterialityQuestion\MaterialityQuestionAuthorizer;
use App\Materiality\Application\MaterialityQuestion\Remove\RemoveMaterialityQuestionRequest;
use App\Materiality\Application\MaterialityQuestion\Remove\RemoveMaterialityQuestionService;
use App\Materiality\Domain\Model\MaterialityQuestion\MaterialityQuestionId;
use App\Materiality\Domain\Model\MaterialityQuestion\MaterialityQuestionNotFoundException;
use App\Materiality\Domain\Model\MaterialityQuestion\MaterialityQuestionRemoved;
use App\Materiality\Domain\Model\MaterialityQuestion\MaterialityQuestionRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Materiality\Domain\Model\MaterialityQuestion\MaterialityQuestionMother;
use App\Tests\Unit\Materiality\Domain\Model\MaterialityQuestion\MaterialityQuestionRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class RemoveMaterialityQuestionServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testRemoves(): void
    {
        $repository = MaterialityQuestionRepositoryMother::inMemory();
        $materialityQuestion = MaterialityQuestionMother::random();
        $repository->add($materialityQuestion);

        $this->createSUT($repository)(new RemoveMaterialityQuestionRequest($materialityQuestion->id()), UserMother::adminUser());

        $this->assertNull($repository->find($materialityQuestion->id()));
        $this->assertInstanceOf(MaterialityQuestionRemoved::class, $this->spySubscriber->lastEvent());
    }

    public function testNotFoundThrows(): void
    {
        $this->expectException(MaterialityQuestionNotFoundException::class);

        $this->createSUT(MaterialityQuestionRepositoryMother::inMemory())(
            new RemoveMaterialityQuestionRequest(MaterialityQuestionId::generate()),
            UserMother::adminUser(),
        );
    }

    public function testOwnerCannotRemove(): void
    {
        $repository = MaterialityQuestionRepositoryMother::inMemory();
        $materialityQuestion = MaterialityQuestionMother::random();
        $repository->add($materialityQuestion);

        $this->expectException(UnauthorizedActionException::class);

        $this->createSUT($repository)(
            new RemoveMaterialityQuestionRequest($materialityQuestion->id()),
            UserMother::fromUsernameAndRole('owner', Role::Owner),
        );
    }

    public function testIsHandled(): void
    {
        $sut = $this->createSUT(MaterialityQuestionRepositoryMother::inMemory());

        $this->assertTrue($sut->isHandled(new RemoveMaterialityQuestionRequest(MaterialityQuestionId::generate())));
    }

    private function createSUT(MaterialityQuestionRepository $repository): RemoveMaterialityQuestionService
    {
        return new RemoveMaterialityQuestionService(
            materialityQuestionRepository: $repository,
            authorizer: new MaterialityQuestionAuthorizer(),
        );
    }
}
