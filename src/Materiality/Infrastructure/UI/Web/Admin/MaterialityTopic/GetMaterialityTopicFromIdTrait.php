<?php

declare(strict_types=1);

namespace App\Materiality\Infrastructure\UI\Web\Admin\MaterialityTopic;

use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopic;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicId;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicRepository;

trait GetMaterialityTopicFromIdTrait
{
    private function getMaterialityTopicFromId(string $materialityTopicId, MaterialityTopicRepository $repository): MaterialityTopic
    {
        $materialityTopic = $repository->find(new MaterialityTopicId($materialityTopicId));

        if (null == $materialityTopic) {
            throw $this->createNotFoundException('Categoría de materialidad no encontrada');
        }

        return $materialityTopic;
    }
}
