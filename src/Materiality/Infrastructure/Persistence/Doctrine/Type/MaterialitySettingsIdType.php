<?php

declare(strict_types=1);

namespace App\Materiality\Infrastructure\Persistence\Doctrine\Type;

use App\Materiality\Domain\Model\MaterialitySettings\MaterialitySettingsId;
use App\Shared\Infrastructure\Persistence\Doctrine\Type\AggregateRootIdTypeTrait;
use Doctrine\DBAL\Types\Type;

class MaterialitySettingsIdType extends Type
{
    use AggregateRootIdTypeTrait;

    public function getName(): string
    {
        return 'materiality_settings_id';
    }

    public function getClassName(): string
    {
        return MaterialitySettingsId::class;
    }
}
