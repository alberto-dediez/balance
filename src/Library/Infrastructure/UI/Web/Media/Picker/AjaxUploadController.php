<?php

declare(strict_types=1);

namespace App\Library\Infrastructure\UI\Web\Media\Picker;

use App\Library\Application\Media\Upload\UploadMediaService;
use App\Library\Domain\Model\Media\MediaRepository;
use App\Library\Domain\Model\Media\MediaType;
use App\Library\Infrastructure\Symfony\Form\Type\MediaPickerType;
use App\Library\Infrastructure\Symfony\Http\UploadedFileMapper;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Uploads a new media from the picker embedded in any other form.
 */
#[Route('/library/media/ajax-upload', name: 'library_media_ajax_upload', methods: ['POST'])]
#[IsGranted('ROLE_USER')]
class AjaxUploadController extends AbstractController
{
    use MediaJsonResponseTrait;

    public function __invoke(
        UploadMediaService $uploadMedia,
        MediaRepository $mediaRepository,
        UploadedFileMapper $uploadedFileMapper,
        LoggedUser $loggedUser,
        LoggedAccount $loggedAccount,
        UrlGeneratorInterface $urlGenerator,
        TranslatorInterface $translator,
        Request $request,
    ): JsonResponse {
        $uploadedFile = $request->files->get('file');

        if (!$uploadedFile instanceof UploadedFile) {
            return $this->json([
                'error' => $translator->trans('No se ha recibido ningún archivo'),
            ], Response::HTTP_BAD_REQUEST);
        }

        if (!$this->isCsrfTokenValid(MediaPickerType::CSRF_TOKEN_ID, (string) $request->request->get('_token'))) {
            return $this->json([
                'error' => $translator->trans('Token de seguridad inválido'),
            ], Response::HTTP_BAD_REQUEST);
        }

        $allowedTypes = array_filter(explode(',', (string) $request->request->get('types', '')));

        if ([] !== $allowedTypes && !in_array(MediaType::fromMimeType((string) $uploadedFile->getMimeType())->value, $allowedTypes, true)) {
            return $this->json([
                'error' => $translator->trans('El tipo de archivo no está permitido'),
            ], Response::HTTP_BAD_REQUEST);
        }

        $account = $loggedAccount();
        $user = $loggedUser();

        try {
            $response = $uploadMedia(
                $uploadedFileMapper($uploadedFile, $account?->id(), $user->id()),
                $user,
            );
        } catch (UnauthorizedActionException $exception) {
            throw $this->createAccessDeniedException($exception->getMessage());
        }

        $media = $mediaRepository->find($response->mediaId);

        if (null === $media) {
            throw $this->createNotFoundException();
        }

        return $this->json([
            'element' => $this->toJsonElement($media, $urlGenerator),
        ]);
    }
}
