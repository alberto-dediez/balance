<?php

declare(strict_types=1);

namespace App\Changelog\Infrastructure\Symfony\Form;

use App\Changelog\Application\Announcement\Publish\PublishAnnouncementRequest;
use App\Shared\Application\CurrentTimezone\CurrentTimezoneProvider;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;

/**
 * @extends AbstractType<PublishAnnouncementRequest>
 */
class PublishAnnouncementType extends AbstractType
{
    public function __construct(
        private readonly CurrentTimezoneProvider $timezoneProvider,
        private readonly bool $announcementsEnabled = true,
    ) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('publishAt', DateTimeType::class, [
                'label' => new TranslatableMessage('Fecha y hora de publicación'),
                'help' => new TranslatableMessage('Déjalo vacío para publicar ahora'),
                'required' => false,
                'widget' => 'single_text',
                'html5' => true,
                'input' => 'datetime_immutable',
                'model_timezone' => date_default_timezone_get(),
                'view_timezone' => $this->timezoneProvider->currentTimezone()->getName(),
            ])
        ;

        if ($this->announcementsEnabled) {
            $builder->add('notifyUsers', CheckboxType::class, [
                'label' => new TranslatableMessage('Notificar a los usuarios'),
                'help' => new TranslatableMessage('Cada usuario recibirá una notificación interna con el título y el resumen en su idioma cuando la novedad se publique'),
                'required' => false,
            ]);
        }

        $builder->add('publish', SubmitType::class, [
            'label' => new TranslatableMessage('Publicar'),
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PublishAnnouncementRequest::class,
        ]);
    }
}
