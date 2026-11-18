<?php

declare(strict_types=1);

namespace App\Assistant\Infrastructure\Symfony\Form;

use App\Assistant\Application\Conversation\SendMessage\SendMessageRequest;
use App\Assistant\Domain\Model\Conversation\Conversation;
use App\Assistant\Domain\Model\Conversation\ConversationId;
use App\Assistant\Domain\Model\Conversation\Message;
use App\Shared\Infrastructure\Symfony\Form\DataTransformer\AggregateRootIdTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Constraints\Count;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

/** @extends AbstractType<SendMessageRequest> */
class SendAssistantMessageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('text', TextareaType::class, [
                'label' => false,
                'attr' => [
                    'placeholder' => new TranslatableMessage('Pregunta o pide algo…'),
                    'rows' => 3,
                ],
                'constraints' => [
                    new NotBlank(),
                    new Length(max: Conversation::MESSAGE_MAX_LENGTH),
                ],
            ])
            ->add('conversationId', HiddenType::class, [
                'required' => false,
            ])
            ->add('screenPath', HiddenType::class, [
                'required' => false,
                'constraints' => [new Length(max: Message::SCREEN_PATH_MAX_LENGTH)],
            ])
            ->add('screenTitle', HiddenType::class, [
                'required' => false,
                'constraints' => [new Length(max: Message::SCREEN_TITLE_MAX_LENGTH)],
            ])
            ->add('attachments', FileType::class, [
                'label' => false,
                'multiple' => true,
                'mapped' => false,
                'required' => false,
                'attr' => ['accept' => '.pdf,.txt,.md,.csv,.json,.html,.png,.jpg,.jpeg,.webp,.gif'],
                'constraints' => [
                    new Count(max: Message::ATTACHMENTS_MAX_COUNT),
                    new All([new File(maxSize: max(1, (int) $options['document_max_bytes']))]),
                ],
            ])
            ->add('send', SubmitType::class, [
                'label' => new TranslatableMessage('Enviar'),
            ])
        ;

        $builder->get('conversationId')->addModelTransformer(new AggregateRootIdTransformer(ConversationId::class));

        foreach (['screenPath', 'screenTitle'] as $field) {
            $builder->get($field)->addModelTransformer(new CallbackTransformer(
                static fn(?string $value): string => $value ?? '',
                static fn(?string $value): string => $value ?? '',
            ));
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SendMessageRequest::class,
            'document_max_bytes' => 10485760,
        ]);
        $resolver->setAllowedTypes('document_max_bytes', 'int');
    }
}
