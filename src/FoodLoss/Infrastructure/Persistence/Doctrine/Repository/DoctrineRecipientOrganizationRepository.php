<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\Persistence\Doctrine\Repository;

use App\FoodLoss\Domain\Model\RecipientOrganization\RecipientOrganization;
use App\FoodLoss\Domain\Model\RecipientOrganization\RecipientOrganizationFilter;
use App\FoodLoss\Domain\Model\RecipientOrganization\RecipientOrganizationId;
use App\FoodLoss\Domain\Model\RecipientOrganization\RecipientOrganizationRepository;
use App\Security\Domain\Model\Account\AccountId;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\When;

#[AsAlias(id: 'repository.recipientOrganization', public: true)]
#[When(env: 'dev')]
#[When(env: 'prod')]
class DoctrineRecipientOrganizationRepository implements RecipientOrganizationRepository
{
    public function __construct(private readonly EntityManagerInterface $manager) {}

    public function add(RecipientOrganization $recipientOrganization): void
    {
        $this->manager->persist($recipientOrganization);
        $this->manager->flush();
    }

    public function remove(RecipientOrganization $recipientOrganization): void
    {
        $this->manager->remove($recipientOrganization);
        $this->manager->flush();
    }

    public function find(RecipientOrganizationId $recipientOrganizationId): ?RecipientOrganization
    {
        $qb = $this->createQueryBuilderFromFilter(new RecipientOrganizationFilter());

        return $qb
            ->andWhere(
                $qb->expr()->eq('e.id', ':id'),
            )
            ->setParameter('id', (string) $recipientOrganizationId)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    private function createQueryBuilderFromFilter(RecipientOrganizationFilter $filter): QueryBuilder
    {
        $qb = $this->manager->createQueryBuilder();

        $qb->select('e')
            ->from(RecipientOrganization::class, 'e')
            ->where(
                $qb->expr()->orX(
                    $qb->expr()->isNull('e.removed'),
                    $qb->expr()->eq('e.removed', ':removed'),
                ),
            )
            ->setParameter('removed', false, Types::BOOLEAN)
        ;

        if (!empty($filter->name)) {
            $qb
                ->andWhere(
                    $qb->expr()->like('e.name', ':name'),
                )
                ->setParameter('name', "%{$filter->name}%")
            ;
        }

        if (!empty($filter->contact)) {
            $qb
                ->andWhere(
                    $qb->expr()->orX(
                        $qb->expr()->like('e.contact.name', ':contact'),
                        $qb->expr()->like('e.contact.email', ':contact'),
                        $qb->expr()->like('e.contact.phone', ':contact'),
                    ),
                )
                ->setParameter('contact', "%{$filter->contact}%")
            ;
        }

        if ($filter->accountId instanceof AccountId) {
            $qb
                ->andWhere(
                    $qb->expr()->eq('e.accountId', ':accountId'),
                )
                ->setParameter('accountId', $filter->accountId)
            ;
        }

        $way = 'ASC' === $filter->orderWay ? 'ASC' : 'DESC';

        match ($filter->orderBy) {
            'registeredAt' => $qb->orderBy('e.registeredAt', $way),
            default => $qb->orderBy('e.name', $way),
        };

        return $qb;
    }

    /** @return array<RecipientOrganization> */
    public function findByFilter(RecipientOrganizationFilter $filter): array
    {
        $qb = $this->createQueryBuilderFromFilter($filter);

        $query = $qb->getQuery();

        if ($filter->limit > 0) {
            $query
                ->setMaxResults($filter->limit)
                ->setFirstResult($filter->page * $filter->limit)
            ;
        }

        return (array) $query->getResult();
    }

    public function totalResultsByFilter(RecipientOrganizationFilter $filter): int
    {
        $qb = $this->createQueryBuilderFromFilter($filter);

        return (int) $qb->select('COUNT(DISTINCT e)')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }
}
