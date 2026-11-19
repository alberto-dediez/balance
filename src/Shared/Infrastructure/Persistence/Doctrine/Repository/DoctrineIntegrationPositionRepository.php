<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Persistence\Doctrine\Repository;

use App\Shared\Domain\Model\Integration\IntegrationPosition;
use App\Shared\Domain\Model\Integration\IntegrationPositionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\When;

#[AsAlias(id: 'repository.integration_positions', public: true)]
#[When(env: 'dev')]
#[When(env: 'prod')]
class DoctrineIntegrationPositionRepository implements IntegrationPositionRepository
{
    public function __construct(private readonly EntityManagerInterface $manager) {}

    public function find(string $source, string $aggregateId): ?IntegrationPosition
    {
        return $this->manager->find(IntegrationPosition::class, ['source' => $source, 'aggregateId' => $aggregateId]);
    }

    public function save(IntegrationPosition $position): void
    {
        $this->manager->persist($position);
        $this->manager->flush();
    }
}
