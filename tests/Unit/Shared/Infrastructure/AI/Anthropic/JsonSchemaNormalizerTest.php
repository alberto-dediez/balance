<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\AI\Anthropic;

use App\Shared\Infrastructure\AI\Anthropic\JsonSchemaNormalizer;
use PHPUnit\Framework\TestCase;

class JsonSchemaNormalizerTest extends TestCase
{
    public function testLowercasesTheTypes(): void
    {
        $schema = (new JsonSchemaNormalizer())->normalize([
            'type' => 'ARRAY',
            'items' => [
                'type' => 'OBJECT',
                'properties' => [
                    'id' => ['type' => 'STRING'],
                    'tags' => ['type' => 'ARRAY', 'items' => ['type' => 'STRING']],
                ],
            ],
        ]);

        $this->assertSame('array', $schema['type']);
        $this->assertSame('object', $schema['items']['type']);
        $this->assertSame('string', $schema['items']['properties']['id']['type']);
        $this->assertSame('array', $schema['items']['properties']['tags']['type']);
        $this->assertSame('string', $schema['items']['properties']['tags']['items']['type']);
    }

    public function testForbidsAdditionalPropertiesInEveryObject(): void
    {
        $schema = (new JsonSchemaNormalizer())->normalize([
            'type' => 'OBJECT',
            'additionalProperties' => true,
            'properties' => [
                'author' => ['type' => 'OBJECT', 'properties' => ['name' => ['type' => 'STRING']]],
                'sections' => ['type' => 'ARRAY', 'items' => ['type' => 'OBJECT', 'properties' => ['title' => ['type' => 'STRING']]]],
            ],
        ]);

        $this->assertFalse($schema['additionalProperties']);
        $this->assertFalse($schema['properties']['author']['additionalProperties']);
        $this->assertFalse($schema['properties']['sections']['items']['additionalProperties']);
        $this->assertArrayNotHasKey('additionalProperties', $schema['properties']['sections']);
        $this->assertArrayNotHasKey('additionalProperties', $schema['properties']['author']['properties']['name']);
    }

    public function testRemovesTheUnsupportedKeys(): void
    {
        $schema = (new JsonSchemaNormalizer())->normalize([
            'type' => 'OBJECT',
            'description' => 'Informe',
            'propertyOrdering' => ['score', 'summary', 'code', 'date'],
            'properties' => [
                'score' => ['type' => 'INTEGER', 'minimum' => 0],
                'summary' => ['type' => 'STRING', 'maxLength' => 500],
                'code' => ['type' => 'STRING', 'format' => 'custom', 'enum' => ['A', 'B']],
                'date' => ['type' => 'STRING', 'format' => 'date'],
            ],
            'required' => ['score', 'summary'],
        ]);

        $this->assertSame([
            'type' => 'object',
            'description' => 'Informe',
            'properties' => [
                'score' => ['type' => 'integer'],
                'summary' => ['type' => 'string'],
                'code' => ['type' => 'string', 'enum' => ['A', 'B']],
                'date' => ['type' => 'string', 'format' => 'date'],
            ],
            'required' => ['score', 'summary'],
            'additionalProperties' => false,
        ], $schema);
    }

    public function testConvertsNullableIntoANullType(): void
    {
        $schema = (new JsonSchemaNormalizer())->normalize(['type' => 'STRING', 'nullable' => true]);

        $this->assertSame(['type' => ['string', 'null']], $schema);
    }

    public function testKeepsANullableTypeThatIsAlreadyAList(): void
    {
        $schema = (new JsonSchemaNormalizer())->normalize(['type' => ['STRING', 'NULL'], 'nullable' => true]);

        $this->assertSame(['type' => ['string', 'null']], $schema);
    }

    public function testForbidsAdditionalPropertiesInANullableObject(): void
    {
        $schema = (new JsonSchemaNormalizer())->normalize(['type' => ['OBJECT', 'NULL'], 'properties' => ['name' => ['type' => 'STRING']]]);

        $this->assertSame(['object', 'null'], $schema['type']);
        $this->assertFalse($schema['additionalProperties']);
    }

    public function testNormalizesTheItemsGivenAsAList(): void
    {
        $schema = (new JsonSchemaNormalizer())->normalize([
            'type' => 'ARRAY',
            'items' => [['type' => 'STRING'], ['type' => 'OBJECT', 'properties' => ['id' => ['type' => 'INTEGER']]]],
        ]);

        $this->assertSame([
            'type' => 'array',
            'items' => [
                ['type' => 'string'],
                ['type' => 'object', 'properties' => ['id' => ['type' => 'integer']], 'additionalProperties' => false],
            ],
        ], $schema);
    }

    public function testNormalizesTheCompositions(): void
    {
        $schema = (new JsonSchemaNormalizer())->normalize([
            'anyOf' => [['type' => 'STRING', 'maxLength' => 10], ['type' => 'OBJECT', 'properties' => ['id' => ['type' => 'STRING']]]],
            'allOf' => [['type' => 'OBJECT', 'properties' => ['name' => ['type' => 'STRING']]]],
        ]);

        $this->assertSame([
            'anyOf' => [['type' => 'string'], ['type' => 'object', 'properties' => ['id' => ['type' => 'string']], 'additionalProperties' => false]],
            'allOf' => [['type' => 'object', 'properties' => ['name' => ['type' => 'string']], 'additionalProperties' => false]],
        ], $schema);
    }

    public function testConvertsOneOfIntoAnyOf(): void
    {
        $schema = (new JsonSchemaNormalizer())->normalize([
            'oneOf' => [['type' => 'STRING'], ['type' => 'INTEGER']],
        ]);

        $this->assertSame(['anyOf' => [['type' => 'string'], ['type' => 'integer']]], $schema);
    }

    public function testKeepsTheOneOfWhenThereIsAnAnyOf(): void
    {
        $schema = (new JsonSchemaNormalizer())->normalize([
            'anyOf' => [['type' => 'STRING']],
            'oneOf' => [['type' => 'INTEGER']],
        ]);

        $this->assertSame(['anyOf' => [['type' => 'string']], 'oneOf' => [['type' => 'INTEGER']]], $schema);
    }

    public function testNormalizesTheDefinitions(): void
    {
        $schema = (new JsonSchemaNormalizer())->normalize([
            'type' => 'OBJECT',
            'properties' => ['author' => ['$ref' => '#/$defs/author']],
            '$defs' => ['author' => ['type' => 'OBJECT', 'properties' => ['name' => ['type' => 'STRING', 'minLength' => 1]]]],
            'definitions' => ['tag' => ['type' => 'OBJECT', 'properties' => ['label' => ['type' => 'STRING']]]],
        ]);

        $this->assertSame(['$ref' => '#/$defs/author'], $schema['properties']['author']);
        $this->assertSame(['type' => 'object', 'properties' => ['name' => ['type' => 'string']], 'additionalProperties' => false], $schema['$defs']['author']);
        $this->assertSame(['type' => 'object', 'properties' => ['label' => ['type' => 'string']], 'additionalProperties' => false], $schema['definitions']['tag']);
    }

    public function testKeepsANormalizedSchema(): void
    {
        $schema = [
            'type' => 'object',
            'properties' => [
                'content' => ['type' => 'string'],
                'items' => ['type' => 'array', 'items' => ['type' => 'object', 'properties' => ['id' => ['type' => ['string', 'null']]], 'additionalProperties' => false]],
            ],
            'required' => ['content'],
            'additionalProperties' => false,
        ];

        $this->assertSame($schema, (new JsonSchemaNormalizer())->normalize($schema));
    }
}
