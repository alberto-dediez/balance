<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\Persistence\Doctrine\Repository;

use App\Security\Domain\Model\EmailLog\EmailLog;
use App\Security\Domain\Model\EmailLog\EmailLogFilter;
use App\Security\Domain\Model\EmailLog\EmailLogId;
use App\Security\Domain\Model\EmailLog\EmailLogRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\When;

#[AsAlias(id: 'repository.email_log', public: true)]
#[When(env: 'dev')]
#[When(env: 'prod')]
class DoctrineEmailLogRepository implements EmailLogRepository
{
    public function __construct(private readonly EntityManagerInterface $manager) {}

    public function add(EmailLog $emailLog): void
    {
        $this->manager->persist($emailLog);
        $this->manager->flush();
    }

    public function remove(EmailLog $emailLog): void
    {
        $this->manager->remove($emailLog);
        $this->manager->flush();
    }

    public function find(EmailLogId $emailLogId): ?EmailLog
    {
        $qb = $this->createQueryBuilderFromFilter(new EmailLogFilter());

        return $qb
            ->andWhere(
                $qb->expr()->eq('e.id', ':id'),
            )
            ->setParameter('id', (string) $emailLogId)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    private function createQueryBuilderFromFilter(EmailLogFilter $filter): QueryBuilder
    {
        $qb = $this->manager->createQueryBuilder();

        $qb->select('e')
            ->from(EmailLog::class, 'e')
        ;

        if (null !== $filter->recipient && '' !== $filter->recipient) {
            $qb
                ->andWhere(
                    $qb->expr()->orX(
                        $qb->expr()->like('e.recipientEmail', ':recipient'),
                        $qb->expr()->like('e.recipientName', ':recipient'),
                    ),
                )
                ->setParameter('recipient', '%' . $filter->recipient . '%')
            ;
        }

        if (null !== $filter->subject && '' !== $filter->subject) {
            $qb
                ->andWhere($qb->expr()->like('e.subject', ':subject'))
                ->setParameter('subject', '%' . $filter->subject . '%')
            ;
        }

        if (null !== $filter->status) {
            $qb
                ->andWhere($qb->expr()->eq('e.status', ':status'))
                ->setParameter('status', $filter->status->value)
            ;
        }

        if (null !== $filter->provider && '' !== $filter->provider) {
            $qb
                ->andWhere($qb->expr()->eq('e.provider', ':provider'))
                ->setParameter('provider', $filter->provider)
            ;
        }

        if (null !== $filter->sentFrom) {
            $qb
                ->andWhere($qb->expr()->gte('e.sentAt', ':sentFrom'))
                ->setParameter('sentFrom', $filter->sentFrom, Types::DATETIME_IMMUTABLE)
            ;
        }

        if (null !== $filter->sentUntil) {
            $qb
                ->andWhere($qb->expr()->lte('e.sentAt', ':sentUntil'))
                ->setParameter('sentUntil', $filter->sentUntil, Types::DATETIME_IMMUTABLE)
            ;
        }

        $way = 'ASC' === $filter->orderWay ? 'ASC' : 'DESC';

        match ($filter->orderBy) {
            'recipient' => $qb->orderBy('e.recipientEmail', $way),
            'status' => $qb->orderBy('e.status', $way),
            'deliveredAt' => $qb->orderBy('e.deliveredAt', $way),
            'firstOpenedAt' => $qb->orderBy('e.firstOpenedAt', $way),
            default => $qb->orderBy('e.sentAt', $way),
        };

        return $qb;
    }

    /** @return list<EmailLog> */
    public function findByFilter(EmailLogFilter $filter): array
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

    public function totalResultsByFilter(EmailLogFilter $filter): int
    {
        $qb = $this->createQueryBuilderFromFilter($filter);

        return (int) $qb->select('COUNT(DISTINCT e)')
            ->resetDQLPart('orderBy')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    /** @return list<EmailLog> */
    public function findSentBefore(\DateTimeImmutable $date): array
    {
        $qb = $this->createQueryBuilderFromFilter(new EmailLogFilter(limit: 0));

        return (array) $qb
            ->andWhere($qb->expr()->lt('e.sentAt', ':date'))
            ->setParameter('date', $date, Types::DATETIME_IMMUTABLE)
            ->getQuery()
            ->getResult()
        ;
    }
}
