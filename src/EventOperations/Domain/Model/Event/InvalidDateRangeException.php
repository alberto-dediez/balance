<?php

declare(strict_types=1);

namespace App\EventOperations\Domain\Model\Event;

class InvalidDateRangeException extends \DomainException
{
    public function __construct(\DateTimeImmutable $startDate, \DateTimeImmutable $endDate)
    {
        parent::__construct(sprintf(
            'La fecha de inicio (%s) no puede ser posterior a la fecha de fin (%s).',
            $startDate->format('d/m/Y'),
            $endDate->format('d/m/Y'),
        ));
    }
}
