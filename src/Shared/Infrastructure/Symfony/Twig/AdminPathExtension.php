<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Symfony\Twig;

use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class AdminPathExtension extends AbstractExtension
{
    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly string $adminPrefix,
    ) {}

    /** @return list<TwigFunction> */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('is_admin_path', $this->isAdminPath(...)),
        ];
    }

    public function isAdminPath(): bool
    {
        if ('' === $this->adminPrefix) {
            return true;
        }

        $request = $this->requestStack->getMainRequest();

        if (null === $request) {
            return true;
        }

        $pathInfo = $request->getPathInfo();

        return $pathInfo === $this->adminPrefix || str_starts_with($pathInfo, $this->adminPrefix . '/');
    }
}
