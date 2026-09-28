<?php

declare(strict_types=1);

namespace App\Tests\Unit\Materiality\Application\MaterialityTopic\Translate;

use App\Materiality\Application\MaterialityTopic\MaterialityTopicAuthorizer;
use App\Materiality\Application\MaterialityTopic\Translate\TranslateMaterialityTopicRequest;
use App\Materiality\Application\MaterialityTopic\Translate\TranslateMaterialityTopicService;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicId;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicNotFoundException;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicRepository;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicTranslated;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicMother;
use App\Tests\Unit\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use PHPUnit\Framework\TestCase;

class TranslateMaterialityTopicServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        FakeLocaleSetter::reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    protected function tearDown(): void
    {
        FakeLocaleSetter::reset();
    }

    public function testAddsTranslationWithoutTouchingBase(): void
    {
        $repository = MaterialityTopicRepositoryMother::inMemory();
        $materialityTopic = MaterialityTopicMother::fromName('Nombre base');
        $repository->add($materialityTopic);

        $this->createSUT($repository)(
            new TranslateMaterialityTopicRequest($materialityTopic->id(), 'English name', 'English description', Locale::English),
            UserMother::adminUser(),
        );

        $this->assertSame('Nombre base', $materialityTopic->name());
        $this->assertCount(1, $materialityTopic->translations());
        $this->assertInstanceOf(MaterialityTopicTranslated::class, $this->spySubscriber->lastEvent());

        FakeLocaleSetter::set(Locale::English);

        $this->assertSame('English name', $materialityTopic->name());
        $this->assertSame('English description', $materialityTopic->description());
    }

    public function testSameLocaleUpdatesInsteadOfDuplicating(): void
    {
        $repository = MaterialityTopicRepositoryMother::inMemory();
        $materialityTopic = MaterialityTopicMother::fromName('Nombre base');
        $repository->add($materialityTopic);

        $sut = $this->createSUT($repository);
        $sut(new TranslateMaterialityTopicRequest($materialityTopic->id(), 'First', '', Locale::English), UserMother::adminUser());
        $sut(new TranslateMaterialityTopicRequest($materialityTopic->id(), 'Second', '', Locale::English), UserMother::adminUser());

        $this->assertCount(1, $materialityTopic->translations());

        FakeLocaleSetter::set(Locale::English);

        $this->assertSame('Second', $materialityTopic->name());
    }

    public function testNotFoundThrows(): void
    {
        $this->expectException(MaterialityTopicNotFoundException::class);

        $this->createSUT(MaterialityTopicRepositoryMother::inMemory())(
            new TranslateMaterialityTopicRequest(MaterialityTopicId::generate(), 'name', '', Locale::English),
            UserMother::adminUser(),
        );
    }

    public function testOwnerCannotTranslate(): void
    {
        $repository = MaterialityTopicRepositoryMother::inMemory();
        $materialityTopic = MaterialityTopicMother::random();
        $repository->add($materialityTopic);

        $this->expectException(UnauthorizedActionException::class);

        $this->createSUT($repository)(
            new TranslateMaterialityTopicRequest($materialityTopic->id(), 'name', '', Locale::English),
            UserMother::fromUsernameAndRole('owner', Role::Owner),
        );
    }

    private function createSUT(MaterialityTopicRepository $repository): TranslateMaterialityTopicService
    {
        return new TranslateMaterialityTopicService(
            materialityTopicRepository: $repository,
            authorizer: new MaterialityTopicAuthorizer(),
        );
    }
}
