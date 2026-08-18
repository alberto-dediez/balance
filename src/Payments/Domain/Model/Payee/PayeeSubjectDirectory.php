<?php

declare(strict_types=1);

namespace App\Payments\Domain\Model\Payee;

interface PayeeSubjectDirectory
{
    /** @return array<string, string> */
    public function subjects(PayeeType $type): array;

    public function label(PayeeType $type, string $subjectId): ?string;
}
