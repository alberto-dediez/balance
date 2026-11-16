<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Assistant;

use App\Shared\Application\Assistant\InvalidToolArgumentsException;
use App\Shared\Application\Assistant\ToolArgumentsFactory;
use App\Shared\Application\Assistant\ToolContext;
use App\Shared\Application\Assistant\ToolParameter;
use PHPUnit\Framework\TestCase;

class ToolArgumentsFactoryTest extends TestCase
{
    /**
     * @dataProvider validValues
     *
     * @param list<string> $expected
     */
    public function testNormalizesValidValues(ToolParameter $parameter, mixed $raw, array $expected): void
    {
        $arguments = ToolArgumentsFactory::create([$parameter], [$parameter->name => $raw], $this->context());

        $this->assertSame($expected, $arguments->strings($parameter->name));
    }

    /** @return iterable<string, array{ToolParameter, mixed, list<string>}> */
    public static function validValues(): iterable
    {
        yield 'string trimmed' => [ToolParameter::string('title', 'Título', ''), '  Migrar DNS  ', ['Migrar DNS']];
        yield 'text' => [ToolParameter::text('description', 'Descripción', ''), "Línea 1\nLínea 2", ["Línea 1\nLínea 2"]];
        yield 'integer from string' => [ToolParameter::integer('limit', 'Límite', '', 1, 50), '20', ['20']];
        yield 'integer' => [ToolParameter::integer('limit', 'Límite', '', 1, 50), 5, ['5']];
        yield 'decimal with comma' => [ToolParameter::decimal('hours', 'Horas', '', 0, 99999.99), '2,50', ['2.5']];
        yield 'decimal float' => [ToolParameter::decimal('hours', 'Horas', '', 0, 99999.99), 0.5, ['0.5']];
        yield 'boolean true' => [ToolParameter::boolean('done', 'Hecha', ''), true, ['1']];
        yield 'boolean false string' => [ToolParameter::boolean('done', 'Hecha', ''), 'false', ['0']];
        yield 'date' => [ToolParameter::date('startsAt', 'Inicio', ''), '2026-11-11', ['2026-11-11']];
        yield 'datetime with seconds' => [ToolParameter::dateTime('scheduledAt', 'Fecha', ''), '2026-11-11T10:30:00', ['2026-11-11 10:30']];
        yield 'enum' => [ToolParameter::enum('status', 'Estado', '', ['open', 'closed']), 'open', ['open']];
        yield 'id lowercased' => [ToolParameter::id('taskId', 'Tarea', ''), '0190F3A4-1B2C-7D3E-8F40-123456789ABC', ['0190f3a4-1b2c-7d3e-8f40-123456789abc']];
        yield 'multiple' => [ToolParameter::string('actionTexts', 'Acciones', '')->multiple(), ['Uno', ' Dos '], ['Uno', 'Dos']];
        yield 'multiple from a single value' => [ToolParameter::string('actionTexts', 'Acciones', '')->multiple(), 'Uno', ['Uno']];
    }

    /**
     * @dataProvider invalidValues
     */
    public function testRejectsInvalidValues(ToolParameter $parameter, mixed $raw): void
    {
        $this->expectException(InvalidToolArgumentsException::class);

        ToolArgumentsFactory::create([$parameter], [$parameter->name => $raw], $this->context());
    }

    /** @return iterable<string, array{ToolParameter, mixed}> */
    public static function invalidValues(): iterable
    {
        yield 'string too long' => [ToolParameter::string('title', 'Título', '', 5), 'Demasiado largo'];
        yield 'integer not numeric' => [ToolParameter::integer('limit', 'Límite', ''), 'veinte'];
        yield 'integer below min' => [ToolParameter::integer('limit', 'Límite', '', 1, 50), 0];
        yield 'integer above max' => [ToolParameter::integer('limit', 'Límite', '', 1, 50), 51];
        yield 'decimal not numeric' => [ToolParameter::decimal('hours', 'Horas', ''), 'dos'];
        yield 'decimal below min' => [ToolParameter::decimal('hours', 'Horas', '', 0), '-1'];
        yield 'boolean' => [ToolParameter::boolean('done', 'Hecha', ''), 'quizá'];
        yield 'date format' => [ToolParameter::date('startsAt', 'Inicio', ''), '11/11/2026'];
        yield 'date impossible' => [ToolParameter::date('startsAt', 'Inicio', ''), '2026-02-30'];
        yield 'datetime format' => [ToolParameter::dateTime('scheduledAt', 'Fecha', ''), '2026-11-11'];
        yield 'enum' => [ToolParameter::enum('status', 'Estado', '', ['open']), 'closed'];
        yield 'id' => [ToolParameter::id('taskId', 'Tarea', ''), 'not-a-uuid'];
        yield 'list on a single parameter' => [ToolParameter::string('title', 'Título', ''), ['Uno', 'Dos']];
    }

    public function testRejectsAMissingRequiredParameter(): void
    {
        try {
            ToolArgumentsFactory::create([ToolParameter::string('title', 'Título', '')->required()], [], $this->context());
            $this->fail('Expected exception');
        } catch (InvalidToolArgumentsException $exception) {
            $this->assertSame(['«title»: es obligatorio.'], $exception->errors());
        }
    }

    public function testAnEmptyStringOfARequiredParameterIsMissing(): void
    {
        $this->expectException(InvalidToolArgumentsException::class);

        ToolArgumentsFactory::create([ToolParameter::string('title', 'Título', '')->required()], ['title' => '   '], $this->context());
    }

    public function testRejectsAnUnknownParameter(): void
    {
        try {
            ToolArgumentsFactory::create([ToolParameter::string('title', 'Título', '')], ['other' => 'x'], $this->context());
            $this->fail('Expected exception');
        } catch (InvalidToolArgumentsException $exception) {
            $this->assertSame(['«other»: parámetro desconocido.'], $exception->errors());
        }
    }

    public function testDeferredParametersAreNotRequired(): void
    {
        $arguments = ToolArgumentsFactory::create(
            [ToolParameter::id('taskId', 'Tarea', '')->required(), ToolParameter::string('text', 'Texto', '')->required()],
            ['text' => 'Bajar TTL'],
            $this->context(),
            ['taskId'],
        );

        $this->assertFalse($arguments->has('taskId'));
        $this->assertSame(['text' => ['Bajar TTL']], $arguments->all());
    }

    public function testOptionalParametersWithoutValueAreAbsent(): void
    {
        $arguments = ToolArgumentsFactory::create([ToolParameter::string('title', 'Título', '')], ['title' => null], $this->context());

        $this->assertFalse($arguments->has('title'));
        $this->assertNull($arguments->string('title'));
        $this->assertSame([], $arguments->all());
    }

    public function testDateTimeIsConvertedFromTheContextTimezoneToTheServerOne(): void
    {
        $arguments = ToolArgumentsFactory::create(
            [ToolParameter::dateTime('scheduledAt', 'Fecha', '')],
            ['scheduledAt' => '2026-11-11 10:30'],
            new ToolContext(new \DateTimeImmutable('2026-11-11 08:00:00'), new \DateTimeZone('Europe/Madrid')),
        );

        $dateTime = $arguments->dateTime('scheduledAt');

        $this->assertNotNull($dateTime);
        $this->assertSame(date_default_timezone_get(), $dateTime->getTimezone()->getName());
        $this->assertSame(
            (new \DateTimeImmutable('2026-11-11 09:30:00', new \DateTimeZone('UTC')))->getTimestamp(),
            $dateTime->getTimestamp(),
        );
    }

    public function testTypedAccessors(): void
    {
        $arguments = ToolArgumentsFactory::create(
            [
                ToolParameter::integer('limit', 'Límite', ''),
                ToolParameter::decimal('hours', 'Horas', ''),
                ToolParameter::boolean('done', 'Hecha', ''),
                ToolParameter::date('startsAt', 'Inicio', ''),
            ],
            ['limit' => '7', 'hours' => '1.25', 'done' => 'true', 'startsAt' => '2026-01-02'],
            $this->context(),
        );

        $this->assertSame(7, $arguments->int('limit'));
        $this->assertSame('1.25', $arguments->decimal('hours'));
        $this->assertTrue($arguments->bool('done'));
        $this->assertSame('2026-01-02', $arguments->date('startsAt')?->format('Y-m-d'));
    }

    public function testCollectsEveryErrorTogether(): void
    {
        try {
            ToolArgumentsFactory::create(
                [
                    ToolParameter::string('title', 'Título', '')->required(),
                    ToolParameter::id('projectId', 'Proyecto', ''),
                    ToolParameter::integer('limit', 'Límite', '', 1, 50),
                ],
                ['projectId' => 'x', 'limit' => 100, 'unknown' => 1],
                $this->context(),
            );
            $this->fail('Expected exception');
        } catch (InvalidToolArgumentsException $exception) {
            $this->assertCount(4, $exception->errors());
        }
    }

    private function context(): ToolContext
    {
        return new ToolContext(new \DateTimeImmutable('2026-11-11 10:00:00'), new \DateTimeZone('UTC'));
    }
}
