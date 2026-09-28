<?php

declare(strict_types=1);

namespace App\Materiality\Infrastructure\Persistence\Doctrine\Type;

use App\Materiality\Domain\Model\MaterialityQuestion\MaterialityQuestionId;
use App\Shared\Infrastructure\Persistence\Doctrine\Type\AggregateRootIdTypeTrait;
use Doctrine\DBAL\Types\Type;

class MaterialityQuestionIdType extends Type
{
    use AggregateRootIdTypeTrait;

    public function getName(): string
    {
        return 'materiality_question_id';
    }

    public function getClassName(): string
    {
        return MaterialityQuestionId::class;
    }
}
