<?php

declare(strict_types=1);

namespace App\Library\Application\Media\List;

use App\Library\Domain\Model\Media\Media;

class ListMediaResponse
{
    /**
     * @param Media[] $media
     */
    public function __construct(public array $media = [], public int $totalResults = 0) {}
}
