<?php

declare(strict_types=1);

namespace App\Property\Infrastructure\Symfony\Form;

use App\Property\Application\Property\List\ListPropertiesRequest;
use App\Property\Infrastructure\Symfony\Form\Type\CountryIdType;
use App\Property\Infrastructure\Symfony\Form\Type\DestinationIdType;
use App\Security\Infrastructure\Symfony\Form\Type\AccountIdType;
use App\Shared\Infrastructure\Symfony\Form\BaseFilterType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;

class ListPropertiesType extends BaseFilterType
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
        ;

        if ($options['admin']) {
            $builder
                ->add('accountId', AccountIdType::class, [
                    'label' => new TranslatableMessage('Cuenta'),
                    'required' => false,
                    'placeholder' => new TranslatableMessage('Todas las cuentas'),
                ])
            ;
        }

        parent::buildForm($builder, $options);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ListPropertiesRequest::class,
            'admin' => false,
        ]);
    }
}
