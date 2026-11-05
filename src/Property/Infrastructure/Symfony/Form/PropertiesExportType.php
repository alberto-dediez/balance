<?php

declare(strict_types=1);

namespace App\Property\Infrastructure\Symfony\Form;

use App\Property\Application\Property\Export\PropertiesExportFilter;
use App\Property\Infrastructure\Symfony\Form\Type\CountryIdType;
use App\Property\Infrastructure\Symfony\Form\Type\DestinationIdType;
use App\Property\Infrastructure\Symfony\Form\Type\OperatingRegimeType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;

/** @extends AbstractType<PropertiesExportFilter> */
class PropertiesExportType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => new TranslatableMessage('Nombre'),
                'required' => false,
            ])
            ->add('destinationId', DestinationIdType::class, [
                'label' => new TranslatableMessage('Destino'),
                'required' => false,
                'placeholder' => new TranslatableMessage('Todos los destinos'),
            ])
            ->add('countryId', CountryIdType::class, [
                'label' => new TranslatableMessage('País'),
                'required' => false,
                'placeholder' => new TranslatableMessage('Todos los países'),
            ])
            ->add('operatingRegime', OperatingRegimeType::class, [
                'label' => new TranslatableMessage('Régimen de explotación'),
                'required' => false,
                'placeholder' => new TranslatableMessage('Todos los regímenes'),
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PropertiesExportFilter::class,
        ]);
    }
}
