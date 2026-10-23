<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\Persistence\InMemory\Repository;

use App\Security\Domain\Model\EmailLog\EmailLog;
use App\Security\Domain\Model\EmailLog\EmailLogFilter;
use App\Security\Domain\Model\EmailLog\EmailLogId;
use App\Security\Domain\Model\EmailLog\EmailLogRepository;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class InMemoryEmailLogRepository implements EmailLogRepository
{
    /** @param array<string, EmailLog> $emailLogs */
    public function __construct(private array $emailLogs = []) {}

    public function add(EmailLog $emailLog): void
    {
        $this->emailLogs[(string) $emailLog->id()] = $emailLog;
    }

    public function remove(EmailLog $emailLog): void
    {
        unset($this->emailLogs[(string) $emailLog->id()]);
    }

    public function find(EmailLogId $emailLogId): ?EmailLog
    {
        return $this->emailLogs[(string) $emailLogId] ?? null;
    }

    /** @return list<EmailLog> */
    public function findByFilter(EmailLogFilter $filter): array
    {
        $result = $this->applyFilter($filter);

        if ($filter->limit > 0) {
            $result = array_slice($result, $filter->page * $filter->limit, $filter->limit);
        }

        return $result;
    }

    public function totalResultsByFilter(EmailLogFilter $filter): int
    {
        return count($this->applyFilter($filter));
    }

    /** @return list<EmailLog> */
    public function findSentBefore(\DateTimeImmutable $date): array
    {
        return array_values(array_filter(
            $this->emailLogs,
            fn(EmailLog $emailLog): bool => $emailLog->sentAt() < $date,
        ));
    }

    /**
     * @return list<EmailLog>
     */
    private function applyFilter(EmailLogFilter $filter): array
    {
        $result = array_filter($this->emailLogs, function (EmailLog $emailLog) use ($filter): bool {
            if (null !== $filter->recipient && '' !== $filter->recipient
                && !str_contains(mb_strtolower($emailLog->recipientEmail()), mb_strtolower($filter->recipient))
                && !str_contains(mb_strtolower($emailLog->recipientName()), mb_strtolower($filter->recipient))) {
                return false;
            }

            if (null !== $filter->subject && '' !== $filter->subject
                && !str_contains(mb_strtolower($emailLog->subject()), mb_strtolower($filter->subject))) {
                return false;
            }

            if (null !== $filter->status && $emailLog->status() !== $filter->status) {
                return false;
            }

            if (null !== $filter->provider && '' !== $filter->provider && $emailLog->provider() !== $filter->provider) {
                return false;
            }

            if (null !== $filter->sentFrom && $emailLog->sentAt() < $filter->sentFrom) {
                return false;
            }

            if (null !== $filter->sentUntil && $emailLog->sentAt() > $filter->sentUntil) {
                return false;
            }

            return true;
        });

        $result = array_values($result);

        $direction = 'ASC' === $filter->orderWay ? 1 : -1;

        usort($result, fn(EmailLog $a, EmailLog $b): int => $direction * match ($filter->orderBy) {
            'recipient' => $a->recipientEmail() <=> $b->recipientEmail(),
            'status' => $a->status()->value <=> $b->status()->value,
            'deliveredAt' => $a->deliveredAt() <=> $b->deliveredAt(),
            'firstOpenedAt' => $a->firstOpenedAt() <=> $b->firstOpenedAt(),
            default => $a->sentAt() <=> $b->sentAt(),
        });

        return $result;
    }
}
