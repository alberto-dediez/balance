<?php

declare(strict_types=1);

namespace App\Bioscore\Application\BioscoreCatalogItem\Sync;

use App\Bioscore\Application\BioscoreCatalogItem\BioscoreCatalogItemAuthorizer;
use App\Bioscore\Application\BioscoreCatalogItem\CatalogTargetFinder;
use App\Bioscore\Application\BioscoreCatalogItem\Register\RegisterBioscoreCatalogItemRequest;
use App\Bioscore\Application\BioscoreCatalogItem\Register\RegisterBioscoreCatalogItemService;
use App\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreCatalog;
use App\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreCatalogGateway;
use App\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreCatalogItem;
use App\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreCatalogItemFilter;
use App\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreCatalogItemId;
use App\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreCatalogItemRepository;
use App\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreCatalogSyncCompleted;
use App\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreMappingStatus;
use App\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreUnavailableException;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Shared\Domain\Event\DomainEventPublisher;
use Psr\Log\LoggerInterface;

final class SyncBioscoreCatalogsService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    private int $registered = 0;
    private int $updated = 0;
    private int $autoMapped = 0;
    private int $withdrawn = 0;
    private int $restored = 0;

    /** @var list<BioscoreCatalogItem> */
    private array $newPending = [];

    /** @var list<BioscoreCatalogItem> */
    private array $invalidated = [];

    public function __construct(
        private BioscoreCatalogItemRepository $repository,
        private BioscoreCatalogGateway $gateway,
        private CatalogTargetFinder $targetFinder,
        private RegisterBioscoreCatalogItemService $registerItem,
        private BioscoreCatalogItemAuthorizer $authorizer,
        private LoggerInterface $logger,
    ) {}

    public function __invoke(SyncBioscoreCatalogsRequest $request, User $user): SyncBioscoreCatalogsResponse
    {
        $this->authorizer->denyUnlessCanDoThat(
            new BioscoreCatalogItem(id: BioscoreCatalogItemId::generate()),
            SecurityAction::Admin,
            $user,
        );

        $this->reset();

        $failedCatalogs = [];
        $catalogs = null !== $request->catalog ? [$request->catalog] : BioscoreCatalog::cases();

        foreach ($catalogs as $catalog) {
            try {
                $entries = $this->gateway->fetch($catalog);
            } catch (BioscoreUnavailableException $e) {
                $this->logger->warning('Bioscore catalog sync failed', ['catalog' => $catalog->value, 'error' => $e->getMessage()]);

                $failedCatalogs[] = $catalog;

                continue;
            }

            $seenIds = [];

            foreach ($entries as $entry) {
                $this->synchronize($catalog, $entry->bioscoreId, $entry->name, $entry->unit, $request->now, $user);

                $seenIds[] = $entry->bioscoreId;
            }

            $this->withdrawMissing($catalog, $seenIds, $request->now);
        }

        $response = new SyncBioscoreCatalogsResponse(
            registered: $this->registered,
            updated: $this->updated,
            autoMapped: $this->autoMapped,
            withdrawn: $this->withdrawn,
            restored: $this->restored,
            newPending: $this->newPending,
            invalidated: $this->invalidated,
            failedCatalogs: $failedCatalogs,
        );

        $this->publishCompletion($response);

        $this->logger->info('Bioscore catalogs synchronized', [
            'registered' => $response->registered,
            'updated' => $response->updated,
            'autoMapped' => $response->autoMapped,
            'withdrawn' => $response->withdrawn,
            'restored' => $response->restored,
            'failedCatalogs' => array_map(static fn(BioscoreCatalog $catalog): string => $catalog->value, $response->failedCatalogs),
        ]);

        return $response;
    }

    private function synchronize(BioscoreCatalog $catalog, int $bioscoreId, string $name, string $unit, \DateTimeImmutable $now, User $user): void
    {
        $item = $this->repository->findByCatalogAndBioscoreId($catalog, $bioscoreId);
        $isNew = null === $item;

        if (null === $item) {
            $item = $this->registerItem->__invoke(
                new RegisterBioscoreCatalogItemRequest(
                    catalog: $catalog,
                    bioscoreId: $bioscoreId,
                    name: $name,
                    unit: $unit,
                    seenAt: $now,
                ),
                $user,
            )->item;

            ++$this->registered;
        } else {
            $wasWithdrawn = $item->isWithdrawn();
            $hadChanges = $name !== $item->name() || $unit !== $item->unit();

            $item->refresh($name, $unit, $now);

            if ($hadChanges) {
                ++$this->updated;
            }

            if ($wasWithdrawn) {
                ++$this->restored;
            }
        }

        $wasInvalidated = false;

        if ($item->isMapped() && null === $this->targetFinder->find($catalog, (string) $item->targetId())) {
            $item->unmap();

            $wasInvalidated = true;
        }

        if (BioscoreMappingStatus::Unmapped === $item->status()) {
            $candidates = $this->targetFinder->matching($catalog, $item->name(), $item->unit());

            if (1 === count($candidates)) {
                $item->map($candidates[0]->id, automatic: true);

                ++$this->autoMapped;
            }
        }

        if (BioscoreMappingStatus::Unmapped === $item->status()) {
            if ($isNew) {
                $this->newPending[] = $item;
            } elseif ($wasInvalidated) {
                $this->invalidated[] = $item;
            }
        }

        $this->repository->add($item);
    }

    /** @param list<int> $seenIds */
    private function withdrawMissing(BioscoreCatalog $catalog, array $seenIds, \DateTimeImmutable $now): void
    {
        $available = $this->repository->findByFilter(
            new BioscoreCatalogItemFilter(catalog: $catalog, withdrawn: false, limit: 0),
        );

        foreach ($available as $item) {
            if (in_array($item->bioscoreId(), $seenIds, true)) {
                continue;
            }

            $item->withdraw($now);

            $this->repository->add($item);

            ++$this->withdrawn;
        }
    }

    private function publishCompletion(SyncBioscoreCatalogsResponse $response): void
    {
        if (!$response->hasNews()) {
            return;
        }

        DomainEventPublisher::instance()->publish(new BioscoreCatalogSyncCompleted(
            newPending: $this->summarize($response->newPending),
            invalidated: $this->summarize($response->invalidated),
            failedCatalogs: array_map(static fn(BioscoreCatalog $catalog): string => $catalog->value, $response->failedCatalogs),
            registered: $response->registered,
            updated: $response->updated,
            autoMapped: $response->autoMapped,
            withdrawn: $response->withdrawn,
            restored: $response->restored,
        ));
    }

    /**
     * @param list<BioscoreCatalogItem> $items
     *
     * @return list<array{itemId: string, catalog: string, bioscoreId: int, name: string}>
     */
    private function summarize(array $items): array
    {
        return array_map(
            static fn(BioscoreCatalogItem $item): array => [
                'itemId' => (string) $item->id(),
                'catalog' => $item->catalog()->value,
                'bioscoreId' => $item->bioscoreId(),
                'name' => $item->name(),
            ],
            $items,
        );
    }

    private function reset(): void
    {
        $this->registered = 0;
        $this->updated = 0;
        $this->autoMapped = 0;
        $this->withdrawn = 0;
        $this->restored = 0;
        $this->newPending = [];
        $this->invalidated = [];
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof SyncBioscoreCatalogsRequest;
    }
}
