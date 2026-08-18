<?php

declare(strict_types=1);

namespace App\Balance\Infrastructure\UI\Web\Admin\Treasury\View;

final readonly class TreasuryAlert
{
    public function __construct(
        public string $message,
        public ?string $actionLabel = null,
        public ?string $actionPath = null,
        public string $level = 'warning',
    ) {}
}
