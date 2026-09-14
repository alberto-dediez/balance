<?php

declare(strict_types=1);

namespace App\Changelog\Domain\Model\ChangelogEntry;

class InvalidChangelogEntryException extends \RuntimeException
{
    public function __construct(string $fileName, string $reason)
    {
        parent::__construct(sprintf('Changelog entry file "%s" is invalid: %s', $fileName, $reason));
    }
}
