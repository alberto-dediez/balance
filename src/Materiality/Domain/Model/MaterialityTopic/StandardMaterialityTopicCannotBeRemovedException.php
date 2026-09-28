<?php

declare(strict_types=1);

namespace App\Materiality\Domain\Model\MaterialityTopic;

final class StandardMaterialityTopicCannotBeRemovedException extends \DomainException
{
    public function __construct(MaterialityTopicId $materialityTopicId)
    {
        parent::__construct(sprintf('Standard materiality topic "%s" cannot be removed.', (string) $materialityTopicId));
    }
}
