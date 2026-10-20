<?php

declare(strict_types=1);

namespace App\Security\Domain\Model\EmailLog;

interface EmailLogRepository
{
    public function add(EmailLog $emailLog): void;

    public function remove(EmailLog $emailLog): void;

    public function find(EmailLogId $emailLogId): ?EmailLog;

    /** @return list<EmailLog> */
    public function findByFilter(EmailLogFilter $filter): array;

    public function totalResultsByFilter(EmailLogFilter $filter): int;

    /** @return list<EmailLog> */
    public function findSentBefore(\DateTimeImmutable $date): array;
}
