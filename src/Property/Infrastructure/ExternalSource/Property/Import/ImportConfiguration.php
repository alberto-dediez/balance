<?php

declare(strict_types=1);

namespace App\Property\Infrastructure\ExternalSource\Property\Import;

use App\Property\Application\Property\Import\FieldExtractor;
use App\Property\Domain\Model\Property\ExternalSource;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

#[Autoconfigure(tags: ['property.import.configuration'])]
interface ImportConfiguration
{
    public function getFieldExtractor(): FieldExtractor;

    public function getSource(): ExternalSource;
}
