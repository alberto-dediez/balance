<?php

declare(strict_types=1);

namespace App\Assistant\Application\Engine;

final class DailyLimitReachedException extends \DomainException
{
    public function __construct()
    {
        parent::__construct('Has alcanzado el límite diario del asistente');
    }
}
