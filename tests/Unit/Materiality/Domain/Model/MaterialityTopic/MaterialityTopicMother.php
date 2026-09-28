<?php

declare(strict_types=1);

namespace App\Tests\Unit\Materiality\Domain\Model\MaterialityTopic;

use App\Materiality\Domain\Model\MaterialityTopic\MaterialityScope;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopic;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicDirection;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicId;
use App\Materiality\Domain\Model\MaterialityTopic\StandardScope3Category;
use App\Shared\Domain\Model\Locale\Locale;

class MaterialityTopicMother
{
    public static function registeredMaterialityTopic(): MaterialityTopic
    {
        return self::fromName('materiality-topic-name');
    }

    public static function fromName(string $name): MaterialityTopic
    {
        return self::from(
            name: $name,
            id: (string) MaterialityTopicId::generate(),
        );
    }

    public static function fromNameAndId(string $name, string $id): MaterialityTopic
    {
        return self::from(
            name: $name,
            id: $id,
        );
    }

    public static function from(
        string $name,
        string $id,
        MaterialityScope $scope = MaterialityScope::Scope3,
        ?MaterialityTopicDirection $direction = MaterialityTopicDirection::Upstream,
        ?int $number = null,
        bool $standard = false,
        int $position = 0,
    ): MaterialityTopic {
        $materialityTopic = new MaterialityTopic(
            id: new MaterialityTopicId($id),
        );
        $materialityTopic->register(
            number: $number,
            scope: $scope,
            direction: $direction,
            name: $name,
            description: $name . ' description',
            position: $position,
            standard: $standard,
        );

        return $materialityTopic;
    }

    public static function standard(StandardScope3Category $category, ?string $id = null): MaterialityTopic
    {
        $materialityTopic = new MaterialityTopic(
            id: new MaterialityTopicId($id ?? (string) MaterialityTopicId::generate()),
        );
        $materialityTopic->register(
            number: $category->value,
            scope: MaterialityScope::Scope3,
            direction: $category->direction(),
            name: (string) $category->name(Locale::Spanish),
            description: (string) $category->description(Locale::Spanish),
            position: $category->value,
            standard: true,
        );
        $materialityTopic->translate(
            (string) $category->name(Locale::English),
            (string) $category->description(Locale::English),
            Locale::English,
        );

        return $materialityTopic;
    }

    public static function random(): MaterialityTopic
    {
        return self::from(
            name: 'materiality-topic-' . rand(100, 999),
            id: (string) MaterialityTopicId::generate(),
        );
    }
}
