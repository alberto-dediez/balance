<?php

declare(strict_types=1);

namespace App\DataExchange\Domain\Model\Exportation;

use Symfony\Component\Translation\TranslatableMessage;

final class ExportColumnDefinition
{
    public function __construct(
        public string $key,
        public string|TranslatableMessage $label,
        public bool $selectedByDefault = true,
    ) {}
}
