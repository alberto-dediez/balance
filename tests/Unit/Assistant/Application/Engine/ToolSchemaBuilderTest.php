<?php

declare(strict_types=1);

namespace App\Tests\Unit\Assistant\Application\Engine;

use App\Assistant\Application\Engine\ToolSchemaBuilder;
use App\Shared\Application\Assistant\ToolParameter;
use PHPUnit\Framework\TestCase;

class ToolSchemaBuilderTest extends TestCase
{
    public function testBuildsTheJsonSchemaOfTheParameters(): void
    {
        $schema = (new ToolSchemaBuilder())->schemaOf(FakeAssistantTool::write(
            'register_task',
            ToolParameter::id('projectId', 'Proyecto', 'Proyecto de la tarea.')->required(),
            ToolParameter::string('title', 'Título', 'Título.', 255)->required(),
            ToolParameter::enum('type', 'Tipo', 'Tipo.', ['none', 'bug']),
            ToolParameter::date('startsAt', 'Inicio', 'Inicio.'),
            ToolParameter::decimal('estimatedHours', 'Horas', 'Horas.', 0, 99999.99),
            ToolParameter::string('actionTexts', 'Acciones', 'Acciones.')->multiple(),
        ));

        $this->assertSame('object', $schema['type']);
        $this->assertFalse($schema['additionalProperties']);
        $this->assertSame(['projectId', 'title'], $schema['required']);
        $this->assertSame(['type' => 'string', 'description' => 'Proyecto de la tarea. UUID.'], $schema['properties']['projectId']);
        $this->assertSame(['type' => 'string', 'description' => 'Título.', 'maxLength' => 255], $schema['properties']['title']);
        $this->assertSame(['none', 'bug'], $schema['properties']['type']['enum']);
        $this->assertStringContainsString('AAAA-MM-DD', $schema['properties']['startsAt']['description']);
        $this->assertSame(['type' => 'number', 'description' => 'Horas.', 'minimum' => 0, 'maximum' => 99999.99], $schema['properties']['estimatedHours']);
        $this->assertSame(['type' => 'array', 'description' => 'Acciones.', 'items' => ['type' => 'string', 'maxLength' => 255]], $schema['properties']['actionTexts']);
    }
}
