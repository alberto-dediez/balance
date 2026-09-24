<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\UI\Web;

use App\Security\Application\UnauthorizedActionException;
use App\Security\Application\User\CancelTwoFactorEnrolment\CancelTwoFactorEnrolmentRequest;
use App\Security\Application\User\CancelTwoFactorEnrolment\CancelTwoFactorEnrolmentService;
use App\Security\Application\User\ConfirmTwoFactorEnrolment\ConfirmTwoFactorEnrolmentRequest;
use App\Security\Application\User\ConfirmTwoFactorEnrolment\ConfirmTwoFactorEnrolmentService;
use App\Security\Application\User\DisableTwoFactor\DisableTwoFactorRequest;
use App\Security\Application\User\DisableTwoFactor\DisableTwoFactorService;
use App\Security\Application\User\RequestTwoFactorEnrolment\RequestTwoFactorEnrolmentRequest;
use App\Security\Application\User\RequestTwoFactorEnrolment\RequestTwoFactorEnrolmentService;
use App\Security\Application\User\TwoFactor\TwoFactorPolicy;
use App\Security\Domain\Model\User\TotpAuthenticator;
use App\Security\Domain\Model\User\TwoFactorCodeInvalidException;
use App\Security\Domain\Model\User\TwoFactorMethod;
use App\Security\Domain\Model\User\TwoFactorNotPendingException;
use App\Security\Domain\Model\User\User;
use App\Security\Infrastructure\Symfony\Form\TwoFactorCodeType;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CsrfProtected;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatorInterface;

#[IsGranted('ROLE_USER')]
class ProfileTwoFactorController extends AbstractController
{
    public function __construct(private string $issuer = '') {}

    #[Route('/profile/two-factor', name: 'profile_two_factor', methods: ['GET'])]
    public function index(LoggedUser $loggedUser, TwoFactorPolicy $policy): Response
    {
        $user = $loggedUser();

        return $this->render('@security/profile/two-factor.html.twig', [
            'user' => $user,
            'currentMethod' => $user->twoFactorMethod(),
            'required' => $policy->isRequired(),
        ]);
    }

    #[Route('/profile/two-factor/email', name: 'profile_two_factor_email', methods: ['GET'])]
    #[CsrfProtected('two-factor-email')]
    public function email(
        LoggedUser $loggedUser,
        RequestTwoFactorEnrolmentService $requestEnrolment,
    ): Response {
        $user = $loggedUser();

        if (TwoFactorMethod::Email === $user->twoFactorMethod()) {
            return $this->redirectToRoute('profile_two_factor');
        }

        try {
            $requestEnrolment(new RequestTwoFactorEnrolmentRequest($user->id(), TwoFactorMethod::Email), $user);
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        $this->addFlash('success', new TranslatableMessage('Segundo factor activado por email'));

        return $this->redirectToRoute('profile_two_factor');
    }

    #[Route('/profile/two-factor/totp', name: 'profile_two_factor_totp', methods: ['GET'])]
    #[CsrfProtected('two-factor-totp')]
    public function totp(
        LoggedUser $loggedUser,
        RequestTwoFactorEnrolmentService $requestEnrolment,
    ): Response {
        $user = $loggedUser();

        try {
            $requestEnrolment(new RequestTwoFactorEnrolmentRequest($user->id(), TwoFactorMethod::Totp), $user);
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->redirectToRoute('profile_two_factor_confirm');
    }

    #[Route('/profile/two-factor/confirm', name: 'profile_two_factor_confirm', methods: ['GET', 'POST'])]
    public function confirm(
        LoggedUser $loggedUser,
        ConfirmTwoFactorEnrolmentService $confirmEnrolment,
        CancelTwoFactorEnrolmentService $cancelEnrolment,
        TotpAuthenticator $totp,
        TwoFactorPolicy $policy,
        TranslatorInterface $translator,
        Request $request,
    ): Response {
        $user = $loggedUser();
        $pendingSecret = $user->pendingTotpSecret();

        if (null === $pendingSecret) {
            return $this->redirectToRoute('profile_two_factor');
        }

        $form = $this->createForm(TwoFactorCodeType::class);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var string $code */
            $code = $form->get('code')->getData();

            try {
                $confirmEnrolment(new ConfirmTwoFactorEnrolmentRequest($user->id(), $code), $user);

                $this->addFlash('success', new TranslatableMessage('Segundo factor activado con la aplicación de autenticación'));

                return $this->redirectToRoute('profile_two_factor');
            } catch (UnauthorizedActionException $e) {
                throw $this->createAccessDeniedException($e->getMessage());
            } catch (TwoFactorCodeInvalidException $e) {
                if ($e->isLocked()) {
                    return $this->restartEnrolment($user, $cancelEnrolment);
                }

                $form->get('code')->addError(new FormError($translator->trans('Código incorrecto. Te quedan {n} intentos.', ['n' => $e->attemptsLeft()])));
            } catch (TwoFactorNotPendingException) {
                return $this->restartEnrolment($user, $cancelEnrolment);
            }
        }

        $provisioningUri = $totp->provisioningUri($pendingSecret, $user->username(), $this->issuer);

        return $this->render('@security/profile/two-factor-confirm.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
            'qrSvg' => '' === $provisioningUri ? '' : $this->qrSvg($provisioningUri),
            'manualKey' => $totp->manualKey($pendingSecret),
            'currentMethod' => $user->twoFactorMethod(),
            'required' => $policy->isRequired(),
        ]);
    }

    #[Route('/profile/two-factor/cancel', name: 'profile_two_factor_cancel', methods: ['GET'])]
    #[CsrfProtected('two-factor-cancel')]
    public function cancel(
        LoggedUser $loggedUser,
        CancelTwoFactorEnrolmentService $cancelEnrolment,
    ): Response {
        $user = $loggedUser();

        if (!$user->hasPendingTotpEnrolment()) {
            return $this->redirectToRoute('profile_two_factor');
        }

        try {
            $cancelEnrolment(new CancelTwoFactorEnrolmentRequest($user->id()), $user);
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        $this->addFlash('warning', new TranslatableMessage('Activación del segundo factor cancelada'));

        return $this->redirectToRoute('profile_two_factor');
    }

    #[Route('/profile/two-factor/disable', name: 'profile_two_factor_disable', methods: ['GET'])]
    #[CsrfProtected('two-factor-disable')]
    public function disable(
        LoggedUser $loggedUser,
        DisableTwoFactorService $disableTwoFactor,
        TwoFactorPolicy $policy,
    ): Response {
        if ($policy->isRequired()) {
            throw $this->createAccessDeniedException('Two factor authentication is required.');
        }

        $user = $loggedUser();

        try {
            $disableTwoFactor(new DisableTwoFactorRequest($user->id()), $user);
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        $this->addFlash('warning', new TranslatableMessage('Segundo factor desactivado'));

        return $this->redirectToRoute('profile_two_factor');
    }

    private function restartEnrolment(User $user, CancelTwoFactorEnrolmentService $cancelEnrolment): Response
    {
        $cancelEnrolment(new CancelTwoFactorEnrolmentRequest($user->id()), $user);

        $this->addFlash('danger', new TranslatableMessage('Demasiados intentos. Vuelve a empezar la activación'));

        return $this->redirectToRoute('profile_two_factor');
    }

    private function qrSvg(string $provisioningUri): string
    {
        $writer = new Writer(new ImageRenderer(new RendererStyle(200), new SvgImageBackEnd()));

        return (string) preg_replace('/^<\?xml[^>]*>\s*/', '', $writer->writeString($provisioningUri));
    }
}
