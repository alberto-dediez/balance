<?php

declare(strict_types=1);

namespace App\Changelog\Infrastructure\Persistence\InMemory\Repository;

use App\Changelog\Domain\Model\Announcement\Announcement;
use App\Changelog\Domain\Model\Announcement\AnnouncementFilter;
use App\Changelog\Domain\Model\Announcement\AnnouncementId;
use App\Changelog\Domain\Model\Announcement\AnnouncementRepository;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class InMemoryAnnouncementRepository implements AnnouncementRepository
{
    /** @var array<string, Announcement> */
    private array $announcements = [];

    public function add(Announcement $announcement): void
    {
        $this->announcements[(string) $announcement->id()] = $announcement;
    }

    public function remove(Announcement $announcement): void
    {
        unset($this->announcements[(string) $announcement->id()]);
    }

    public function clear(): void
    {
        $this->announcements = [];
    }

    public function find(AnnouncementId $announcementId): ?Announcement
    {
        $announcement = $this->announcements[(string) $announcementId] ?? null;

        if (!$announcement instanceof Announcement || $announcement->isRemoved()) {
            return null;
        }

        return $announcement;
    }

    public function findByFilter(AnnouncementFilter $filter): array
    {
        $announcements = $this->sorted($this->matching($filter), $filter->orderWay);

        if ($filter->limit <= 0) {
            return $announcements;
        }

        return array_slice($announcements, $filter->page * $filter->limit, $filter->limit);
    }

    public function totalResultsByFilter(AnnouncementFilter $filter): int
    {
        return count($this->matching($filter));
    }

    public function findScheduledToPublishUntil(\DateTimeImmutable $until): array
    {
        $announcements = array_values(array_filter(
            $this->announcements,
            static fn(Announcement $announcement): bool => !$announcement->isRemoved()
                && $announcement->isScheduled()
                && $announcement->scheduledAt() <= $until,
        ));

        usort($announcements, static fn(Announcement $a, Announcement $b): int => $a->scheduledAt() <=> $b->scheduledAt());

        return $announcements;
    }

    /**
     * @return list<Announcement>
     */
    private function matching(AnnouncementFilter $filter): array
    {
        return array_values(array_filter(
            $this->announcements,
            fn(Announcement $announcement): bool => $this->matches($announcement, $filter),
        ));
    }

    private function matches(Announcement $announcement, AnnouncementFilter $filter): bool
    {
        if ($announcement->isRemoved()) {
            return false;
        }

        if (null !== $filter->category && $announcement->category() !== $filter->category) {
            return false;
        }

        if (null !== $filter->status && $announcement->status() !== $filter->status) {
            return false;
        }

        if (in_array($filter->text, [null, ''], true)) {
            return true;
        }

        $needle = mb_strtolower($filter->text);

        return str_contains(mb_strtolower($announcement->title()), $needle) || str_contains(mb_strtolower($announcement->summary()), $needle);
    }

    /**
     * @param list<Announcement> $announcements
     *
     * @return list<Announcement>
     */
    private function sorted(array $announcements, string $orderWay): array
    {
        $direction = 'ASC' === $orderWay ? 1 : -1;

        usort($announcements, static function (Announcement $a, Announcement $b) use ($direction): int {
            $byDate = $a->date() <=> $b->date();

            if (0 !== $byDate) {
                return $byDate * $direction;
            }

            return ($a->registeredAt() <=> $b->registeredAt()) * $direction;
        });

        return $announcements;
    }
}
