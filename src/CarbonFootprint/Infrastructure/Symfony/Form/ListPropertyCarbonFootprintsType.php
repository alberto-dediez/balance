<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Symfony\Form;

use App\CarbonFootprint\Application\PropertyCarbonFootprint\List\ListPropertyCarbonFootprintsRequest;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\OrganizationalCarbonFootprintStatus;
use App\Property\Infrastructure\Symfony\Form\Type\PropertyIdType;
use App\Shared\Infrastructure\Symfony\Form\BaseFilterType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;

class ListPropertyCarbonFootprintsType extends BaseFilterType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('propertyId', PropertyIdType::class, [
                'label' => new TranslatableMessage('Propiedad'),
                'required' => false,
                'placeholder' => new TranslatableMessage('Todas las propiedades'),
            ])
            ->add('year', IntegerType::class, [
                'label' => new TranslatableMessage('Año'),
                'required' => false,
            ])
            ->add('status', EnumType::class, [
                'label' => new TranslatableMessage('Estado'),
                'required' => false,
                'class' => OrganizationalCarbonFootprintStatus::class,
                'placeholder' => new TranslatableMessage('Todos los estados'),
                'choice_label' => fn(OrganizationalCarbonFootprintStatus $status) => new TranslatableMessage($status->value, [], 'organizationalCarbonFootprintStatusType'),
            ])
        ;

        parent::buildForm($builder, $options);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ListPropertyCarbonFootprintsRequest::class,
        ]);
    }
}
