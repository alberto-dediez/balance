<?php

declare(strict_types=1);

namespace App\Tests\Unit\Materiality\Application\MaterialityQuestion\Translate;

use App\Materiality\Application\MaterialityQuestion\MaterialityQuestionAuthorizer;
use App\Materiality\Application\MaterialityQuestion\Translate\TranslateMaterialityQuestionRequest;
use App\Materiality\Application\MaterialityQuestion\Translate\TranslateMaterialityQuestionService;
use App\Materiality\Domain\Model\MaterialityQuestion\MaterialityQuestionId;
use App\Materiality\Domain\Model\MaterialityQuestion\MaterialityQuestionNotFoundException;
use App\Materiality\Domain\Model\MaterialityQuestion\MaterialityQuestionRepository;
use App\Materiality\Domain\Model\MaterialityQuestion\MaterialityQuestionTranslated;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\Materiality\Domain\Model\MaterialityQuestion\MaterialityQuestionMother;
use App\Tests\Unit\Materiality\Domain\Model\MaterialityQuestion\MaterialityQuestionRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use PHPUnit\Framework\TestCase;

class TranslateMaterialityQuestionServiceTest extends TestCase
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
        $repository = MaterialityQuestionRepositoryMother::inMemory();
        $materialityQuestion = MaterialityQuestionMother::fromText('Texto base');
        $repository->add($materialityQuestion);

        $this->createSUT($repository)(
            new TranslateMaterialityQuestionRequest($materialityQuestion->id(), 'English text', 'English help', Locale::English),
            UserMother::adminUser(),
        );

        $this->assertSame('Texto base', $materialityQuestion->text());
        $this->assertInstanceOf(MaterialityQuestionTranslated::class, $this->spySubscriber->lastEvent());

        FakeLocaleSetter::set(Locale::English);

        $this->assertSame('English text', $materialityQuestion->text());
        $this->assertSame('English help', $materialityQuestion->help());
    }

    public function testSameLocaleUpdatesInsteadOfDuplicating(): void
    {
        $repository = MaterialityQuestionRepositoryMother::inMemory();
        $materialityQuestion = MaterialityQuestionMother::random();
        $repository->add($materialityQuestion);

        $sut = $this->createSUT($repository);
        $sut(new TranslateMaterialityQuestionRequest($materialityQuestion->id(), 'First', '', Locale::English), UserMother::adminUser());
        $sut(new TranslateMaterialityQuestionRequest($materialityQuestion->id(), 'Second', '', Locale::English), UserMother::adminUser());

        $this->assertCount(1, $materialityQuestion->translations());
    }

    public function testNotFoundThrows(): void
    {
        $this->expectException(MaterialityQuestionNotFoundException::class);

        $this->createSUT(MaterialityQuestionRepositoryMother::inMemory())(
            new TranslateMaterialityQuestionRequest(MaterialityQuestionId::generate(), 'text', '', Locale::English),
            UserMother::adminUser(),
        );
    }

    public function testOwnerCannotTranslate(): void
    {
        $repository = MaterialityQuestionRepositoryMother::inMemory();
        $materialityQuestion = MaterialityQuestionMother::random();
        $repository->add($materialityQuestion);

        $this->expectException(UnauthorizedActionException::class);

        $this->createSUT($repository)(
            new TranslateMaterialityQuestionRequest($materialityQuestion->id(), 'text', '', Locale::English),
            UserMother::fromUsernameAndRole('owner', Role::Owner),
        );
    }

    public function testIsHandled(): void
    {
        $sut = $this->createSUT(MaterialityQuestionRepositoryMother::inMemory());

        $this->assertTrue($sut->isHandled(new TranslateMaterialityQuestionRequest(MaterialityQuestionId::generate(), 'text', 'help', Locale::English)));
    }

    private function createSUT(MaterialityQuestionRepository $repository): TranslateMaterialityQuestionService
    {
        return new TranslateMaterialityQuestionService(
            materialityQuestionRepository: $repository,
            authorizer: new MaterialityQuestionAuthorizer(),
        );
    }
}
