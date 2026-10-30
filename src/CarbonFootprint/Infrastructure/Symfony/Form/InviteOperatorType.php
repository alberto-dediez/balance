<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Symfony\Form;

use App\Property\Domain\Model\Property\InvalidOperatorEmailException;
use App\Property\Domain\Model\Property\OperatorContact;
use App\Shared\Infrastructure\Symfony\Form\Type\LocaleType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Validator\Constraints as Assert;

/** @extends AbstractType<array<string, mixed>> */
class InviteOperatorType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('contactEmail', EmailType::class, [
                'label' => new TranslatableMessage('Email'),
                'required' => true,
                'help' => new TranslatableMessage('Los cambios se guardan también en la ficha de la propiedad'),
                'invalid_message' => new TranslatableMessage('El email no es válido.'),
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Email(),
                    new Assert\Length(max: 255),
                ],
            ])
            ->add('contactName', TextType::class, [
                'label' => new TranslatableMessage('Nombre de contacto'),
                'required' => false,
                'constraints' => [
                    new Assert\Length(max: 255),
                ],
            ])
            ->add('contactLocale', LocaleType::class, [
                'label' => new TranslatableMessage('Idioma del correo'),
                'required' => true,
                'constraints' => [
                    new Assert\NotNull(),
                ],
            ])
            ->add('register', SubmitType::class, [
                'label' => new TranslatableMessage('Enviar invitación'),
            ])
        ;

        $builder->get('contactEmail')
            ->addModelTransformer(new CallbackTransformer(
                fn(?string $email): ?string => $email,
                function (?string $email): ?string {
                    try {
                        return OperatorContact::from($email, null, null)->email();
                    } catch (InvalidOperatorEmailException $exception) {
                        throw new TransformationFailedException($exception->getMessage(), 0, $exception);
                    }
                },
            ))
        ;
    }
}
