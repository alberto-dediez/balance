<?php

declare(strict_types=1);

namespace App\Materiality\Domain\Model\MaterialityQuestion;

final class MaterialityQuestionNotFoundException extends \RuntimeException
{
    public function __construct(MaterialityQuestionId $materialityQuestionId)
    {
        parent::__construct(sprintf('Materiality question "%s" not found.', (string) $materialityQuestionId));
    }
}
