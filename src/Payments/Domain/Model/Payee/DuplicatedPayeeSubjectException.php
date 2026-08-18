<?php

declare(strict_types=1);

namespace App\Payments\Domain\Model\Payee;

class DuplicatedPayeeSubjectException extends \RuntimeException
{
    public function __construct(PayeeType $type, string $subjectId)
    {
        parent::__construct(
            sprintf('Ya existe un beneficiario de tipo %s para %s.', $type->value, $subjectId),
            409,
        );
    }
}
