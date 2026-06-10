<?php

declare(strict_types=1);

namespace App\Tests\Unit\DataExchange\Application\Exportation\Execute;

use App\DataExchange\Application\Exportation\Execute\ExecuteExportationRequest;
use App\DataExchange\Application\Exportation\Execute\ExecuteExportationService;
use App\DataExchange\Domain\Model\Exportation\ExportationExecuted;
use App\DataExchange\Domain\Model\Exportation\ExportationFailed;
use App\DataExchange\Domain\Model\Exportation\ExportationFormat;
use App\DataExchange\Domain\Model\Exportation\ExportationTypeFactory;
use App\DataExchange\Domain\Model\Exportation\ExportWriter;
use App\DataExchange\Infrastructure\Persistence\InMemory\Repository\InMemoryExportationRepository;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\DataExchange\Application\Exportation\FakeExportationType;
use App\Tests\Unit\DataExchange\Application\Exportation\SpyExportStorage;
use App\Tests\Unit\DataExchange\Application\Exportation\SpyExportWriter;
use App\Tests\Unit\DataExchange\Domain\Model\Exportation\ExportationMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class ExecuteExportationServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testUseCase(): void
    {
        $accountId = AccountId::generate();
        $exportation = ExportationMother::registered(
            type: 'users',
            accountId: $accountId,
            columns: ['username', 'roles'],
        );

        $repository = new InMemoryExportationRepository();
        $repository->add($exportation);

        $storage = new SpyExportStorage();
        $writer = new SpyExportWriter();

        $sut = new ExecuteExportationService(
            $repository,
            new ExportationTypeFactory([new FakeExportationType()]),
            $storage,
            $writer,
            $this->translator(),
        );

        $user = UserMother::fromUsernameAndRoleAndAccount('admin', Role::Admin, $accountId);

        $response = $sut(new ExecuteExportationRequest($exportation->id()), $user);

        $this->assertTrue($response->success);

        $persisted = $repository->find($exportation->id());
        $this->assertNotNull($persisted);
        $this->assertTrue($persisted->isCompleted());
        $this->assertNotNull($persisted->file());

        $this->assertSame(1, $storage->storeCalls);
        $this->assertTrue($writer->closed);
        $this->assertCount(3, $writer->rows);
        $this->assertSame(['Username', 'Roles'], $writer->rows[0]);
        $this->assertSame(['alice', 'Admin'], $writer->rows[1]);

        $lastEvent = $this->spySubscriber->lastEvent();
        $this->assertInstanceOf(ExportationExecuted::class, $lastEvent);
    }

    public function testFailureMarksExportationAsFailed(): void
    {
        $exportation = ExportationMother::registered(columns: ['username']);

        $repository = new InMemoryExportationRepository();
        $repository->add($exportation);

        $throwingWriter = new class implements ExportWriter {
            public function open(string $path, ExportationFormat $format): void
            {
                throw new \RuntimeException('disk full');
            }

            public function writeRow(array $cells): void {}

            public function close(): void {}
        };

        $sut = new ExecuteExportationService(
            $repository,
            new ExportationTypeFactory([new FakeExportationType()]),
            new SpyExportStorage(),
            $throwingWriter,
            $this->translator(),
        );

        $user = UserMother::adminUser();

        $response = $sut(new ExecuteExportationRequest($exportation->id()), $user);

        $this->assertFalse($response->success);

        $persisted = $repository->find($exportation->id());
        $this->assertNotNull($persisted);
        $this->assertTrue($persisted->isFailed());

        $events = $this->spySubscriber->events();
        $this->assertNotEmpty(array_filter($events, static fn($event): bool => $event instanceof ExportationFailed));
    }

    public function testIsHandled(): void
    {
        $sut = new ExecuteExportationService(
            new InMemoryExportationRepository(),
            new ExportationTypeFactory([new FakeExportationType()]),
            new SpyExportStorage(),
            new SpyExportWriter(),
            $this->translator(),
        );

        $this->assertTrue($sut->isHandled(new ExecuteExportationRequest(ExportationMother::registered()->id())));
    }

    private function translator(): TranslatorInterface
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')->willReturnCallback(static fn(string $id): string => $id);

        return $translator;
    }
}
