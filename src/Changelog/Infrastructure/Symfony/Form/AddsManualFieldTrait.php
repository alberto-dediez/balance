<?php

declare(strict_types=1);

namespace App\Changelog\Infrastructure\Symfony\Form;

use App\Library\Domain\Model\Media\Media;
use App\Library\Domain\Model\Media\MediaId;
use App\Library\Domain\Model\Media\MediaRepository;
use App\Library\Domain\Model\Media\MediaType;
use App\Library\Domain\Model\Media\MediaVisibility;
use App\Library\Infrastructure\Symfony\Form\Type\MediaPickerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

trait AddsManualFieldTrait
{
    /**
     * @param FormBuilderInterface<mixed> $builder
     */
    private function addManualField(FormBuilderInterface $builder, MediaRepository $mediaRepository): void
    {
        $builder->add('manualId', MediaPickerType::class, [
            'label' => new TranslatableMessage('Manual (PDF)'),
            'required' => false,
            'types' => [MediaType::Document],
            'accept' => 'application/pdf',
            'visibility' => MediaVisibility::System,
            'constraints' => [
                new Callback(static function (mixed $value, ExecutionContextInterface $context) use ($mediaRepository): void {
                    if (!$value instanceof MediaId) {
                        return;
                    }

                    $media = $mediaRepository->find($value);

                    if ($media instanceof Media && 'application/pdf' === $media->mimeType()) {
                        return;
                    }

                    $context->buildViolation('El manual debe ser un PDF')
                        ->setTranslationDomain('messages')
                        ->addViolation()
                    ;
                }),
            ],
        ]);
    }
}
