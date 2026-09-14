<?php

declare(strict_types=1);

namespace App\Changelog\Domain\Model\ChangelogEntry;

class ManualNotFoundException extends \RuntimeException
{
    public function __construct(string $fileName)
    {
        parent::__construct(sprintf('Changelog manual "%s" not found', $fileName));
    }
}
