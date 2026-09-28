<?php

declare(strict_types=1);

namespace App\Materiality\Domain\Model\MaterialityTopic;

final class MaterialityTopicNotFoundException extends \RuntimeException
{
    public function __construct(MaterialityTopicId $materialityTopicId)
    {
        parent::__construct(sprintf('Materiality topic "%s" not found.', (string) $materialityTopicId));
    }
}
