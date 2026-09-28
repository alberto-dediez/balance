<?php

declare(strict_types=1);

namespace App\Materiality\Domain\Model\MaterialityTopic;

final class MaterialityTopicNumberAlreadyUsedException extends \DomainException
{
    public function __construct(int $number)
    {
        parent::__construct(sprintf('Materiality topic number "%d" is already used.', $number));
    }
}
