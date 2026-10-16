<?php

declare(strict_types=1);

namespace App\Bioscore\Infrastructure\Symfony\Form;

use App\Bioscore\Application\BioscoreCatalogItem\CatalogTarget;
use App\Bioscore\Application\BioscoreCatalogItem\UpdateMapping\UpdateBioscoreCatalogItemMappingRequest;
use App\Shared\Infrastructure\Symfony\Form\Type\AutocompleteType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatorInterface;

/** @extends AbstractType<UpdateBioscoreCatalogItemMappingRequest> */
class UpdateBioscoreCatalogItemMappingType extends AbstractType
{
    public function __construct(
        private readonly TranslatorInterface $translator,
    ) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('targetId', AutocompleteType::class, [
                'label' => new TranslatableMessage('Destino en la plataforma'),
                'required' => false,
                'placeholder' => new TranslatableMessage('Sin mapear'),
                'choices' => $this->choices($options['targets']),
            ])
            ->add('ignore', CheckboxType::class, [
                'label' => new TranslatableMessage('Ignorar este elemento'),
                'required' => false,
            ])
            ->add('saveAndReturn', SubmitType::class, [
                'label' => new TranslatableMessage('Guardar y salir'),
            ])
            ->add('save', SubmitType::class, [
                'label' => new TranslatableMessage('Guardar'),
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => UpdateBioscoreCatalogItemMappingRequest::class,
        ]);

        $resolver->setRequired('targets');
        $resolver->setAllowedTypes('targets', 'array');
    }

    /**
     * @param array<string, CatalogTarget> $targets
     *
     * @return array<string, string>
     */
    private function choices(array $targets): array
    {
        $choices = [];

        foreach ($targets as $target) {
            $choices[$this->label($target, $choices)] = $target->id;
        }

        return $choices;
    }

    /** @param array<string, string> $choices */
    private function label(CatalogTarget $target, array $choices): string
    {
        $label = null !== $target->vehicle
            ? sprintf('%s - %s', $target->name, $this->translator->trans($target->vehicle->value, [], 'transportVehicleType'))
            : $target->name;

        $candidate = $label;
        $i = 0;

        while (isset($choices[$candidate])) {
            $candidate = sprintf('%s (%d)', $label, ++$i);
        }

        return $candidate;
    }
}
