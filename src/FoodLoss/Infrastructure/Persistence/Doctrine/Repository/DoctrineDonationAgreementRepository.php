<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\Persistence\Doctrine\Repository;

use App\FoodLoss\Domain\Model\DonationAgreement\DonationAgreement;
use App\FoodLoss\Domain\Model\DonationAgreement\DonationAgreementFilter;
use App\FoodLoss\Domain\Model\DonationAgreement\DonationAgreementId;
use App\FoodLoss\Domain\Model\DonationAgreement\DonationAgreementRepository;
use App\FoodLoss\Domain\Model\RecipientOrganization\RecipientOrganizationId;
use App\Security\Domain\Model\Account\Account;
use App\Security\Domain\Model\Account\AccountId;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\When;

#[AsAlias(id: 'repository.donation_agreement', public: true)]
#[When(env: 'dev')]
#[When(env: 'prod')]
class DoctrineDonationAgreementRepository implements DonationAgreementRepository
{
    public function __construct(private readonly EntityManagerInterface $manager) {}

    public function add(DonationAgreement $donationAgreement): void
    {
        $this->manager->persist($donationAgreement);
        $this->manager->flush();
    }

    public function remove(DonationAgreement $donationAgreement): void
    {
        $this->manager->remove($donationAgreement);
        $this->manager->flush();
    }

    public function find(DonationAgreementId $donationAgreementId): ?DonationAgreement
    {
        $qb = $this->createQueryBuilderFromFilter(new DonationAgreementFilter());

        return $qb
            ->andWhere(
                $qb->expr()->eq('e.id', ':id'),
            )
            ->setParameter('id', (string) $donationAgreementId)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    private function createQueryBuilderFromFilter(DonationAgreementFilter $filter): QueryBuilder
    {
        $qb = $this->manager->createQueryBuilder();

        $qb->select('e')
            ->from(DonationAgreement::class, 'e')
            ->where(
                $qb->expr()->orX(
                    $qb->expr()->isNull('e.removed'),
                    $qb->expr()->eq('e.removed', ':removed'),
                ),
            )
            ->setParameter('removed', false, Types::BOOLEAN)
        ;

        if ($filter->recipientOrganizationId instanceof RecipientOrganizationId) {
            $qb
                ->andWhere(
                    $qb->expr()->eq('e.recipientOrganizationId', ':recipientOrganizationId'),
                )
                ->setParameter('recipientOrganizationId', (string) $filter->recipientOrganizationId)
            ;
        }

        if ($filter->accountId instanceof AccountId) {
            $qb
                ->andWhere(
                    $qb->expr()->eq('e.accountId', ':accountId'),
                )
                ->setParameter('accountId', (string) $filter->accountId)
            ;
        }

        $way = 'ASC' === $filter->orderWay ? 'ASC' : 'DESC';

        match ($filter->orderBy) {
            'registeredAt' => $qb->orderBy('e.registeredAt', $way),
            'startDate' => $qb->orderBy('e.startDate', $way),
            'expirationDate' => $qb->orderBy('e.expirationDate', $way),
            default => $qb->orderBy('e.registeredAt', $way),
        };

        return $qb;
    }

    /** @return array<DonationAgreement> */
    public function findByFilter(DonationAgreementFilter $filter): array
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

    public function totalResultsByFilter(DonationAgreementFilter $filter): int
    {
        $qb = $this->createQueryBuilderFromFilter($filter);

        return (int) $qb->select('COUNT(DISTINCT e)')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    /** @return array<DonationAgreement> */
    public function findExpiringSoon(int $days): array
    {
        $now = new \DateTimeImmutable('today');
        $limit = $now->modify(sprintf('+%d days', $days));

        $qb = $this->createQueryBuilderFromFilter(new DonationAgreementFilter(limit: 0));

        $qb->join(Account::class, 'a', 'ON', 'e.accountId = a.id')
            ->andWhere(
                $qb->expr()->andX(
                    $qb->expr()->eq('a.active', ':accountActive'),
                    $qb->expr()->eq('a.removed', ':accountRemoved'),
                ),
            )
            ->andWhere($qb->expr()->gte('e.expirationDate', ':now'))
            ->andWhere($qb->expr()->lte('e.expirationDate', ':limit'))
            ->andWhere($qb->expr()->isNull('e.expirationWarningNotifiedAt'))
            ->setParameter('removed', false, Types::BOOLEAN)
            ->setParameter('now', $now)
            ->setParameter('limit', $limit)
            ->setParameter('accountActive', true, Types::BOOLEAN)
            ->setParameter('accountRemoved', false, Types::BOOLEAN)
        ;

        return (array) $qb
            ->getQuery()
            ->getResult()
        ;
    }
}
