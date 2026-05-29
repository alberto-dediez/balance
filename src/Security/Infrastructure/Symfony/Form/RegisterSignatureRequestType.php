<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\Symfony\Form;

use App\Security\Application\SignatureRequest\Register\RegisterSignatureRequestRequest;
use App\Security\Domain\Model\SignatureRequest\NotificationChannel;
use App\Security\Infrastructure\Symfony\Form\Type\NotificationChannelType;
use App\Shared\Infrastructure\Symfony\Form\Type\LocaleType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;

/** @extends AbstractType<mixed> */
class RegisterSignatureRequestType extends AbstractType
{
    public function __construct(private readonly bool $smsEnabled) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('documentToSignFile', FileType::class, [
                'label' => new TranslatableMessage('Documento a firmar (PDF)'),
                'required' => true,
                'mapped' => false,
            ])
            ->add('signerName', TextType::class, [
                'label' => new TranslatableMessage('Nombre del firmante'),
                'required' => true,
            ])
            ->add('signerLocale', LocaleType::class, [
                'label' => new TranslatableMessage('Idioma del firmante'),
                'required' => true,
            ])
            ->add('notificationChannel', NotificationChannelType::class, [
                'label' => new TranslatableMessage('Canal de notificación'),
                'required' => true,
                'sms_enabled' => $this->smsEnabled,
            ])
            ->add('signerEmail', EmailType::class, [
                'label' => new TranslatableMessage('Email del firmante'),
                'required' => false,
                'empty_data' => null,
            ])
        ;

        if ($this->smsEnabled) {
            $builder->add('signerPhone', TextType::class, [
                'label' => new TranslatableMessage('Teléfono del firmante'),
                'required' => false,
                'empty_data' => null,
            ]);
        }

        $builder->add('register', SubmitType::class, [
            'label' => new TranslatableMessage('Registrar'),
        ]);

        $builder->addEventListener(FormEvents::POST_SUBMIT, static function (\Symfony\Component\Form\FormEvent $event): void {
            $form = $event->getForm();
            $data = $event->getData();

            if (!$data instanceof RegisterSignatureRequestRequest) {
                return;
            }

            if (NotificationChannel::Email === $data->notificationChannel && empty($data->signerEmail)) {
                $form->get('signerEmail')->addError(new FormError('notification_channel.signer_email_required'));
            }

            if (NotificationChannel::Sms === $data->notificationChannel && empty($data->signerPhone)) {
                $form->get('signerPhone')->addError(new FormError('notification_channel.signer_phone_required'));
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => RegisterSignatureRequestRequest::class,
        ]);
    }
}
