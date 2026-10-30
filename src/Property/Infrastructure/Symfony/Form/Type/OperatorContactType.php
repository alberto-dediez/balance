<?php

declare(strict_types=1);

namespace App\Property\Infrastructure\Symfony\Form\Type;

use App\Property\Domain\Model\Property\InvalidOperatorEmailException;
use App\Property\Domain\Model\Property\OperatorContact;
use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Infrastructure\Symfony\Form\Type\LocaleType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Validator\Constraints as Assert;

/** @extends AbstractType<OperatorContact> */
class OperatorContactType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => new TranslatableMessage('Email'),
                'required' => false,
                'invalid_message' => new TranslatableMessage('El email no es válido.'),
                'setter' => function (OperatorContact &$contact, ?string $value, FormInterface $form): void {
                    $contact = OperatorContact::from($value, $contact->name(), $contact->locale());
                },
            ])
            ->add('name', TextType::class, [
                'label' => new TranslatableMessage('Nombre de contacto'),
                'required' => false,
                'constraints' => [
                    new Assert\Length(max: 255),
                ],
                'setter' => function (OperatorContact &$contact, ?string $value, FormInterface $form): void {
                    $contact = OperatorContact::from($contact->email(), $value, $contact->locale());
                },
            ])
            ->add('locale', LocaleType::class, [
                'label' => new TranslatableMessage('Idioma del correo'),
                'required' => false,
                'placeholder' => new TranslatableMessage('Sin idioma'),
                'setter' => function (OperatorContact &$contact, ?Locale $value, FormInterface $form): void {
                    $contact = OperatorContact::from($contact->email(), $contact->name(), $value);
                },
            ])
        ;

        $builder->get('email')
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

        $builder->get('name')
            ->addModelTransformer(new CallbackTransformer(
                fn(?string $name): ?string => $name,
                function (?string $name): ?string {
                    try {
                        return OperatorContact::from(null, $name, null)->name();
                    } catch (\InvalidArgumentException $exception) {
                        throw new TransformationFailedException($exception->getMessage(), 0, $exception);
                    }
                },
            ))
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => OperatorContact::class,
            'empty_data' => fn(): OperatorContact => new OperatorContact(),
        ]);
    }
}
