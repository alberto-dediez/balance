<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Symfony\Twig;

use App\Shared\Infrastructure\Symfony\Twig\AdminPathExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class AdminPathExtensionTest extends TestCase
{
    public function testEmptyPrefixIsAlwaysAdminPath(): void
    {
        $sut = new AdminPathExtension($this->requestStack('/es/viajes'), '');

        $this->assertTrue($sut->isAdminPath());
    }

    public function testEmptyPrefixWithoutRequestIsAdminPath(): void
    {
        $sut = new AdminPathExtension(new RequestStack(), '');

        $this->assertTrue($sut->isAdminPath());
    }

    public function testPrefixWithoutRequestIsAdminPath(): void
    {
        $sut = new AdminPathExtension(new RequestStack(), '/management');

        $this->assertTrue($sut->isAdminPath());
    }

    public function testPathUnderPrefixIsAdminPath(): void
    {
        $sut = new AdminPathExtension($this->requestStack('/management/es/profile'), '/management');

        $this->assertTrue($sut->isAdminPath());
    }

    public function testPathEqualToPrefixIsAdminPath(): void
    {
        $sut = new AdminPathExtension($this->requestStack('/management'), '/management');

        $this->assertTrue($sut->isAdminPath());
    }

    public function testPathStartingWithPrefixTextIsNotAdminPath(): void
    {
        $sut = new AdminPathExtension($this->requestStack('/managementx/es'), '/management');

        $this->assertFalse($sut->isAdminPath());
    }

    public function testPathOutsidePrefixIsNotAdminPath(): void
    {
        $sut = new AdminPathExtension($this->requestStack('/es/viajes'), '/management');

        $this->assertFalse($sut->isAdminPath());
    }

    public function testRootIsNotAdminPathWithPrefix(): void
    {
        $sut = new AdminPathExtension($this->requestStack('/'), '/management');

        $this->assertFalse($sut->isAdminPath());
    }

    public function testExposesIsAdminPathFunction(): void
    {
        $sut = new AdminPathExtension(new RequestStack(), '');

        $names = array_map(static fn($function): string => $function->getName(), $sut->getFunctions());

        $this->assertSame(['is_admin_path'], $names);
    }

    private function requestStack(string $path): RequestStack
    {
        $requestStack = new RequestStack();
        $requestStack->push(Request::create($path));

        return $requestStack;
    }
}
