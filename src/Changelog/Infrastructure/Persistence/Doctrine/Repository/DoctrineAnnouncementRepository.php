<?php

declare(strict_types=1);

namespace App\Changelog\Infrastructure\Persistence\Doctrine\Repository;

use App\Changelog\Domain\Model\Announcement\Announcement;
use App\Changelog\Domain\Model\Announcement\AnnouncementFilter;
use App\Changelog\Domain\Model\Announcement\AnnouncementId;
use App\Changelog\Domain\Model\Announcement\AnnouncementRepository;
use App\Changelog\Domain\Model\Announcement\AnnouncementStatus;
use App\Changelog\Domain\Model\Announcement\AnnouncementTranslation;
use App\Shared\Domain\Model\Locale\LocaleGetter;
use App\Shared\Infrastructure\Persistence\Doctrine\Repository\DoctrineTranslatableRepositoryTrait;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\When;

#[AsAlias(id: 'repository.announcement', public: true)]
#[When(env: 'dev')]
#[When(env: 'prod')]
class DoctrineAnnouncementRepository implements AnnouncementRepository
{
    use DoctrineTranslatableRepositoryTrait;

    public function __construct(private readonly EntityManagerInterface $manager) {}

    public function add(Announcement $announcement): void
    {
        $this->manager->persist($announcement);
        $this->addTranslations($announcement, AnnouncementTranslation::class, $this->manager);
        $this->manager->flush();
    }

    public function remove(Announcement $announcement): void
    {
        $this->manager->remove($announcement);
        $this->manager->flush();
    }

    public function find(AnnouncementId $announcementId): ?Announcement
    {
        $qb = $this->manager->createQueryBuilder();

        return $qb->select('a')
            ->from(Announcement::class, 'a')
            ->where($qb->expr()->eq('a.id', ':id'))
            ->andWhere($qb->expr()->eq('a.removed', ':removed'))
            ->setParameter('id', (string) $announcementId)
            ->setParameter('removed', false, Types::BOOLEAN)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    /**
     * @return list<Announcement>
     */
    public function findByFilter(AnnouncementFilter $filter): array
    {
        $qb = $this->createQueryBuilderFromFilter($filter);

        $way = 'ASC' === $filter->orderWay ? 'ASC' : 'DESC';

        $qb->orderBy('a.date', $way)
            ->addOrderBy('a.registeredAt', $way)
        ;

        $query = $qb->getQuery();

        if ($filter->limit > 0) {
            $query
                ->setMaxResults($filter->limit)
                ->setFirstResult($filter->page * $filter->limit)
            ;
        }

        return (array) $query->getResult();
    }

    public function totalResultsByFilter(AnnouncementFilter $filter): int
    {
        $count = $this->createQueryBuilderFromFilter($filter)
            ->select('COUNT(DISTINCT a.id)')
            ->getQuery()
            ->getSingleScalarResult()
        ;

        return intval($count);
    }

    /**
     * @return list<Announcement>
     */
    public function findScheduledToPublishUntil(\DateTimeImmutable $until): array
    {
        $qb = $this->manager->createQueryBuilder();

        $qb->select('a')
            ->from(Announcement::class, 'a')
            ->where($qb->expr()->eq('a.removed', ':removed'))
            ->andWhere($qb->expr()->eq('a.published', ':published'))
            ->andWhere($qb->expr()->isNotNull('a.scheduledAt'))
            ->andWhere($qb->expr()->lte('a.scheduledAt', ':until'))
            ->setParameter('removed', false, Types::BOOLEAN)
            ->setParameter('published', false, Types::BOOLEAN)
            ->setParameter('until', $until, Types::DATETIME_IMMUTABLE)
            ->orderBy('a.scheduledAt', 'ASC')
        ;

        return (array) $qb->getQuery()->getResult();
    }

    private function createQueryBuilderFromFilter(AnnouncementFilter $filter): QueryBuilder
    {
        $qb = $this->manager->createQueryBuilder();

        $qb->select('a')
            ->from(Announcement::class, 'a')
            ->leftJoin(AnnouncementTranslation::class, 'trans', 'WITH', 'trans.translatable = a AND trans.locale = :locale')
            ->where($qb->expr()->eq('a.removed', ':removed'))
            ->setParameter('removed', false, Types::BOOLEAN)
            ->setParameter('locale', LocaleGetter::locale())
        ;

        if (!in_array($filter->text, [null, ''], true)) {
            $qb
                ->andWhere(
                    $qb->expr()->orX(
                        $qb->expr()->andX(
                            $qb->expr()->isNull('trans.locale'),
                            $qb->expr()->orX(
                                $qb->expr()->like('LOWER(a.title)', ':text'),
                                $qb->expr()->like('LOWER(a.summary)', ':text'),
                            ),
                        ),
                        $qb->expr()->like('LOWER(trans.title)', ':text'),
                        $qb->expr()->like('LOWER(trans.summary)', ':text'),
                    ),
                )
                ->setParameter('text', '%' . mb_strtolower($filter->text) . '%')
            ;
        }

        if (null !== $filter->category) {
            $qb
                ->andWhere($qb->expr()->eq('a.category', ':category'))
                ->setParameter('category', $filter->category->value)
            ;
        }

        match ($filter->status) {
            AnnouncementStatus::Published => $qb
                ->andWhere($qb->expr()->eq('a.published', ':published'))
                ->setParameter('published', true, Types::BOOLEAN),
            AnnouncementStatus::Scheduled => $qb
                ->andWhere($qb->expr()->eq('a.published', ':published'))
                ->andWhere($qb->expr()->isNotNull('a.scheduledAt'))
                ->setParameter('published', false, Types::BOOLEAN),
            AnnouncementStatus::Draft => $qb
                ->andWhere($qb->expr()->eq('a.published', ':published'))
                ->andWhere($qb->expr()->isNull('a.scheduledAt'))
                ->setParameter('published', false, Types::BOOLEAN),
            null => $qb,
        };

        return $qb;
    }
}
