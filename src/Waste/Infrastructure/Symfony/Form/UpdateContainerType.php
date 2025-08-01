<?php

namespace App\Waste\Infrastructure\Symfony\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Validator\Constraints as Assert;
use App\Waste\Domain\Model\Container\MeasurementMethod;
use App\Waste\Infrastructure\Symfony\Form\Type\VolumeType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use App\Waste\Infrastructure\Symfony\Form\Type\PurposeType;
use App\Waste\Infrastructure\Symfony\Form\Type\WasteIdType;
use App\Waste\Application\Container\Update\UpdateContainerRequest;
use App\Waste\Infrastructure\Symfony\Form\Type\WasteManagerIdType;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use App\Waste\Infrastructure\Symfony\Form\Type\MeasurementMethodType;

class UpdateContainerType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('wasteId', WasteIdType::class, [
                'label' => new TranslatableMessage('Tipología de residuo'),
                'required' => true
            ])
            ->add('purpose', PurposeType::class, [
                'label' => new TranslatableMessage('Propósito'),
                'required' => true
            ])
            ->add('measurementMethod', MeasurementMethodType::class, [
                'label' => new TranslatableMessage('Método de medida'),
                'required' => true
            ])
            ->add('volume', VolumeType::class, [
                'label' => new TranslatableMessage('Volumen (l)'),
                'required' => false,
                'constraints' => [
                    new Assert\Callback(
                        function ($value, ExecutionContextInterface $context, mixed $payload): void {
                            $volume = $value;
                            $measurementMethod = $context->getObject()?->getParent()?->get('measurementMethod')?->getData();

                            if ($measurementMethod === MeasurementMethod::Volume && (null === $volume || '' === $volume)) {
                                $context->buildViolation(new TranslatableMessage('Debes especificar un volumen válido para este contenedor'))
                                    ->atPath('volume')
                                    ->addViolation()
                                ;
                            }
                        }
                    )
                ]
            ])
            ->add('wasteManagerId', WasteManagerIdType::class, [
                'label' => new TranslatableMessage('Gestor de residuos'),
                'required' => true
            ])
            ->add('save', SubmitType::class, [
                'label' => new TranslatableMessage('Guardar')
            ])
            ->add('saveAndReturn', SubmitType::class, [
                'label' => new TranslatableMessage('Guardar y salir')
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => UpdateContainerRequest::class
        ]);
    }
}