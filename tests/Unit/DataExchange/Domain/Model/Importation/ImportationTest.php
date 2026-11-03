<?php

declare(strict_types=1);

namespace App\Tests\Unit\DataExchange\Domain\Model\Importation;

use App\DataExchange\Domain\Model\Importation\ImportationExecuted;
use App\DataExchange\Domain\Model\Importation\ImportationFile;
use App\DataExchange\Domain\Model\Importation\ImportationStatus;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class ImportationTest extends TestCase
{
    private SpyDomainEventSubscriber $spy;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        $this->spy = new SpyDomainEventSubscriber();
    }

    public function testFinishExecutionPublishesTheEventOnce(): void
    {
        $importation = ImportationMother::fromNameAndFileAndStatus('superheroes.csv', new ImportationFile(path: __DIR__ . '/file-to-import.csv'), ImportationStatus::InProgress);

        $importation->finishExecution();
        $importation->finishExecution();

        $this->assertCount(1, $this->executedEvents());
        $this->assertTrue($importation->isExecuted());
    }

    public function testFinishExecutionOnAnExecutedImportationPublishesNothing(): void
    {
        $importation = ImportationMother::fromNameAndFileAndStatus('superheroes.csv', new ImportationFile(path: __DIR__ . '/file-to-import.csv'), ImportationStatus::Executed);

        $importation->finishExecution();

        $this->assertCount(0, $this->spy->events());
    }

    /** @return DomainEvent[] */
    private function executedEvents(): array
    {
        return array_values(array_filter($this->spy->events(), fn(DomainEvent $event): bool => $event instanceof ImportationExecuted));
    }
}
