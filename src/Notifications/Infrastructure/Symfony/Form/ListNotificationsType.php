<?php

declare(strict_types=1);

namespace App\Notifications\Infrastructure\Symfony\Form;

use App\Notifications\Application\Notification\List\ListNotificationsRequest;
use App\Notifications\Domain\Model\Notification\NotificationChannel;
use App\Notifications\Domain\Model\Notification\NotificationStatus;
use App\Shared\Infrastructure\Symfony\Form\BaseFilterType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;

class ListNotificationsType extends BaseFilterType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('channel', EnumType::class, [
                'class' => NotificationChannel::class,
                'label' => new TranslatableMessage('Canal'),
                'required' => false,
                'placeholder' => new TranslatableMessage('Todos los canales'),
            ])
            ->add('status', EnumType::class, [
                'class' => NotificationStatus::class,
                'label' => new TranslatableMessage('Estado'),
                'required' => false,
                'placeholder' => new TranslatableMessage('Todos los estados'),
            ])
        ;

        parent::buildForm($builder, $options);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ListNotificationsRequest::class,
        ]);
    }
}
