<?php

declare(strict_types=1);

namespace App\Security\Domain\Model\EmailLog;

use App\Shared\Domain\Model\Filter;

class EmailLogFilter
{
    use Filter;

    public function __construct(
        public ?string $recipient = null,
        public ?string $subject = null,
        public ?EmailLogStatus $status = null,
        public ?string $provider = null,
        public ?\DateTimeImmutable $sentFrom = null,
        public ?\DateTimeImmutable $sentUntil = null,
        int $limit = 10,
    ) {
        $this->limit = $limit;
        $this->orderWay = 'DESC';
    }
}
