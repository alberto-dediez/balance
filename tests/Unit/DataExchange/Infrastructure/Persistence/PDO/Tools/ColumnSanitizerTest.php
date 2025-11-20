<?php

namespace App\Tests\Unit\DataExchange\Infrastructure\Persistence\PDO\Tools;

use App\DataExchange\Infrastructure\Persistence\PDO\Tools\ColumnSanitizer;
use PHPUnit\Framework\TestCase;

class ColumnSanitizerTest extends TestCase
{
    public function testSanitize()
    {
        $headers = [
            'normal',
            ' Algun ',
            'tiempo pasado',
            '_fue_',
            '',
            'normal',
        ];

        $result = ColumnSanitizer::sanitize($headers);

        $this->assertTrue(is_array($result));
        $this->assertCount(6, $result);
        $this->assertEquals('normal', $result[0]);
        $this->assertEquals('algun', $result[1]);
        $this->assertEquals('tiempo_pasado', $result[2]);
        $this->assertEquals('fue', $result[3]);
        $this->assertEquals('col_4', $result[4]);
        $this->assertEquals('normal_2', $result[5]);
    }
}
