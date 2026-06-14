<?php

declare(strict_types=1);

namespace App\Notifications\Infrastructure\Symfony\Form;

use App\Notifications\Domain\Model\Notification\AudienceType;
use App\Notifications\Domain\Model\Notification\NotificationChannel;
use App\Security\Domain\Model\User\Role;
use App\Security\Infrastructure\Symfony\Form\Type\AccountType;
use App\Security\Infrastructure\Symfony\Form\Type\RoleType;
use App\Security\Infrastructure\Symfony\Form\Type\UserIdType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;

/**
 * @extends AbstractType<SendNotificationForm>
 */
class SendNotificationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('audienceType', EnumType::class, [
                'class' => AudienceType::class,
                'label' => new TranslatableMessage('Destinatarios'),
                'choice_label' => fn(AudienceType $type): TranslatableMessage => new TranslatableMessage(match ($type) {
                    AudienceType::User => (string) new TranslatableMessage('Un usuario concreto'),
                    AudienceType::Account => (string) new TranslatableMessage('Todos los usuarios de una cuenta'),
                    AudienceType::AccountRole => (string) new TranslatableMessage('Usuarios de una cuenta con un rol'),
                    AudienceType::AllPlatform => (string) new TranslatableMessage('Todos los usuarios de la plataforma'),
                }),
            ])
            ->add('targetUser', UserIdType::class, [
                'label' => new TranslatableMessage('Usuario'),
                'required' => false,
                'placeholder' => new TranslatableMessage('Selecciona un usuario'),
            ])
            ->add('targetAccount', AccountType::class, [
                'label' => new TranslatableMessage('Cuenta'),
                'required' => false,
                'placeholder' => new TranslatableMessage('Selecciona una cuenta'),
            ])
            ->add('targetRole', RoleType::class, [
                'label' => new TranslatableMessage('Rol'),
                'required' => false,
                'choices' => Role::casesForAdmins(),
                'placeholder' => new TranslatableMessage('Selecciona un rol'),
            ])
            ->add('channels', EnumType::class, [
                'class' => NotificationChannel::class,
                'label' => new TranslatableMessage('Canales'),
                'multiple' => true,
                'expanded' => true,
                'choices' => [
                    NotificationChannel::Internal,
                    NotificationChannel::Email,
                ],
                'choice_label' => fn(NotificationChannel $channel): TranslatableMessage => new TranslatableMessage(match ($channel) {
                    NotificationChannel::Internal => 'Notificación interna',
                    NotificationChannel::Email => 'Correo electrónico',
                    NotificationChannel::Sms => 'SMS',
                    NotificationChannel::Whatsapp => 'WhatsApp',
                }),
            ])
            ->add('subject', TextType::class, [
                'label' => new TranslatableMessage('Asunto'),
            ])
            ->add('body', TextareaType::class, [
                'label' => new TranslatableMessage('Mensaje'),
                'attr' => ['rows' => 6],
            ])
            ->add('send', SubmitType::class, [
                'label' => new TranslatableMessage('Enviar'),
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SendNotificationForm::class,
        ]);
    }
}
