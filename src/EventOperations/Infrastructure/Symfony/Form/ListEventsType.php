<?php

declare(strict_types=1);

namespace App\EventOperations\Infrastructure\Symfony\Form;

use App\EventOperations\Application\Event\List\ListEventsRequest;
use App\EventOperations\Infrastructure\Symfony\Form\Type\EventSectorType;
use App\EventOperations\Infrastructure\Symfony\Form\Type\EventSizeType;
use App\EventOperations\Infrastructure\Symfony\Form\Type\EventTypologyType;
use App\Property\Infrastructure\Symfony\Form\Type\CompanyIdType;
use App\Property\Infrastructure\Symfony\Form\Type\DestinationIdType;
use App\Property\Infrastructure\Symfony\Form\Type\PropertyIdType;
use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use App\Shared\Infrastructure\Symfony\Form\BaseFilterType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;

class ListEventsType extends BaseFilterType
{
    public function __construct(private readonly LoggedAccount $loggedAccount) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $account = ($this->loggedAccount)();

        $builder
            ->add('name', TextType::class, [
                'label' => new TranslatableMessage('Nombre'),
                'required' => false,
            ])
            ->add('location', DestinationIdType::class, [
                'label' => new TranslatableMessage('Ubicación'),
                'required' => false,
            ])
            ->add('propertyId', PropertyIdType::class, [
                'label' => new TranslatableMessage('Instalación'),
                'required' => false,
                'placeholder' => new TranslatableMessage('Sin instalación'),
            ])
            ->add('companyId', CompanyIdType::class, [
                'label' => new TranslatableMessage('Empresa'),
                'required' => false,
                'placeholder' => new TranslatableMessage('Sin empresa'),
                'account_id' => $account?->id(),
            ])
            ->add('size', EventSizeType::class, [
                'label' => new TranslatableMessage('Tamaño del evento'),
                'required' => false,
            ])
            ->add('sector', EventSectorType::class, [
                'label' => new TranslatableMessage('Sector del evento'),
                'required' => false,
            ])
            ->add('typology', EventTypologyType::class, [
                'label' => new TranslatableMessage('Tipología del evento'),
                'required' => false,
            ])
        ;

        parent::buildForm($builder, $options);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => ListEventsRequest::class,
        ]);
    }
}
