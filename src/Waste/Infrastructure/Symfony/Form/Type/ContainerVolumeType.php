<?php

declare(strict_types=1);

namespace App\Waste\Infrastructure\Symfony\Form\Type;

use App\Waste\Domain\Model\Container\MeasurementMethod;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class ContainerVolumeType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'symbol' => 'l',
            'label' => new TranslatableMessage('Capacidad (l)'),
            'required' => false,
            'constraints' => [
                new Assert\Callback(
                    function ($value, ExecutionContextInterface $context, mixed $payload): void {
                        $volume = $value;
                        $measurementMethod = $context->getObject()?->getParent()?->get('measurementMethod')?->getData();

                        if (MeasurementMethod::Volume === $measurementMethod && (null === $volume || '' === $volume)) {
                            $context->buildViolation(new TranslatableMessage('Debes especificar un volumen válido para este contenedor'))
                                ->atPath('volume')
                                ->addViolation()
                            ;
                        }
                    },
                ),
            ],
            'attr' => [
                'data-controller' => 'showable',
                'data-showable-selector-value' => '[name*="measurementMethod"]',
                'data-showable-container-value' => 'div:not(.input-group)',
                'data-showable-condition-value' => MeasurementMethod::Volume->value,
            ],
        ]);


    }

    public function getParent(): string
    {
        return VolumeType::class;
    }
}
