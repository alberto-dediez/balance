<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Tcpdf;

final class ReportTcpdf extends \TCPDF
{
    private ?\Closure $pageChrome = null;

    public function setPageChrome(\Closure $pageChrome): void
    {
        $this->pageChrome = $pageChrome;
        $this->setPrintHeader(true);
    }

    public function Header(): void
    {
        if (null !== $this->pageChrome) {
            ($this->pageChrome)();
        }
    }
}
