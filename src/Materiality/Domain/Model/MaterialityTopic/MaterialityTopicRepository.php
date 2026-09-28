<?php

declare(strict_types=1);

namespace App\Materiality\Domain\Model\MaterialityTopic;

interface MaterialityTopicRepository
{
    public function add(MaterialityTopic $materialityTopic): void;

    public function remove(MaterialityTopic $materialityTopic): void;

    public function find(MaterialityTopicId $materialityTopicId): ?MaterialityTopic;

    public function findByNumber(int $number): ?MaterialityTopic;

    /** @return array<MaterialityTopic> */
    public function findByFilter(MaterialityTopicFilter $filter): array;

    public function totalResultsByFilter(MaterialityTopicFilter $filter): int;

    /** @return list<MaterialityTopic> */
    public function findActive(): array;
}
