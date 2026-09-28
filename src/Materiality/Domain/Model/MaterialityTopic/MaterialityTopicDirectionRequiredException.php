<?php

declare(strict_types=1);

namespace App\Materiality\Domain\Model\MaterialityTopic;

final class MaterialityTopicDirectionRequiredException extends \DomainException
{
    public function __construct()
    {
        parent::__construct('A value chain direction is required for scope 3 materiality topics.');
    }
}
