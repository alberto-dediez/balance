<?php

namespace App\Shared\Infrastructure\Symfony;

use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\DomainEventSubscriber;
use App\Shared\Domain\Event\EventRegistry;
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

        $taggedServices = $container->findTaggedServiceIds('domain.event');

        foreach ($taggedServices as $class => $tags) {
            EventRegistry::instance()
                ->registerEvent($class::contextName(), $class)
            ;
        }

        EventRegistry::instance()->save();
    }

    protected function build(ContainerBuilder $container): void
    {
        $container->registerForAutoconfiguration(DomainEvent::class)
            ->addTag('domain.event')
        ;

        $container->registerForAutoconfiguration(DomainEventSubscriber::class)
            ->addTag('domain.event.subscriber')
        ;
    }
}
