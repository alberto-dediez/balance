<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Assistant;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class DisabledAssistantToolsPass implements CompilerPassInterface
{
    public const string PARAMETER = 'shared.assistant.disabled_tools';
    public const string TAG = 'shared.assistant.tool';

    public function process(ContainerBuilder $container): void
    {
        /** @var list<class-string> $classes */
        $classes = $container->getParameter(self::PARAMETER);

        foreach ($classes as $class) {
            if (!$container->hasDefinition($class)) {
                throw new \InvalidArgumentException(sprintf('The assistant tool "%s" listed in "%s" does not exist.', $class, self::PARAMETER));
            }

            $container->getDefinition($class)->clearTag(self::TAG);
        }
    }
}
