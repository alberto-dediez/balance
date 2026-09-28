<?php

declare(strict_types=1);

namespace App\Materiality\Domain\Model\MaterialityQuestion;

use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicId;

interface MaterialityQuestionRepository
{
    public function add(MaterialityQuestion $materialityQuestion): void;

    public function remove(MaterialityQuestion $materialityQuestion): void;

    public function find(MaterialityQuestionId $materialityQuestionId): ?MaterialityQuestion;

    /** @return array<MaterialityQuestion> */
    public function findByFilter(MaterialityQuestionFilter $filter): array;

    public function totalResultsByFilter(MaterialityQuestionFilter $filter): int;

    /** @return list<MaterialityQuestion> */
    public function findByTopicId(MaterialityTopicId $topicId): array;
}
