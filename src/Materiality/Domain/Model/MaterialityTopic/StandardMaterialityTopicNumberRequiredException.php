<?php

declare(strict_types=1);

namespace App\Materiality\Domain\Model\MaterialityTopic;

final class StandardMaterialityTopicNumberRequiredException extends \DomainException
{
    public function __construct()
    {
        parent::__construct('A number is required for standard materiality topics.');
    }
}
