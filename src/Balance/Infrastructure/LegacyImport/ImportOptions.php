<?php

declare(strict_types=1);

namespace App\Balance\Infrastructure\LegacyImport;

final readonly class ImportOptions
{
    /**
     * @param list<string>          $skip
     * @param list<string>          $staff
     * @param array<string, string> $accounts
     * @param array<string, string> $properties
     * @param array<string, string> $projects
     * @param array<string, string> $mobilities
     */
    public function __construct(
        public array $skip = [],
        public array $staff = [],
        public array $accounts = [],
        public array $properties = [],
        public array $projects = [],
        public array $mobilities = [],
        public ?string $consultant = null,
        public bool $createProperties = false,
    ) {}
}
