<?php

namespace App\Shared\Infrastructure\Symfony;

use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

class Kernel extends BaseKernel implements CompilerPassInterface
{
    use MicroKernelTrait;

    public function process(ContainerBuilder $container): void
    {
        if ('test' === $this->environment) {
            // prevents the security token to be cleared
            $container->getDefinition('security.token_storage')->clearTag('kernel.reset');

            // prevents Doctrine entities to be detached
            $container->getDefinition('doctrine')->clearTag('kernel.reset');
        }
    }
}
