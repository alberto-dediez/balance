<?php

declare(strict_types=1);

namespace App\Balance\Infrastructure\UI\Web\CreditPurchase;

use App\Balance\Application\CreditPurchase\Request\CreditPurchaseBillingDefaults;
use App\Balance\Application\CreditPurchase\Request\CreditPurchaseQuoter;
use App\Balance\Application\CreditPurchase\Request\RequestCreditPurchaseRequest;
use App\Balance\Application\CreditPurchase\Request\RequestCreditPurchaseService;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseQuoteChangedException;
use App\Balance\Domain\Model\CreditPurchase\ProjectNotCompensableException;
use App\Balance\Domain\Model\Project\ProjectRepository;
use App\Balance\Domain\Model\Project\ProjectType;
use App\Balance\Infrastructure\Symfony\Form\RequestCreditPurchaseType;
use App\Balance\Infrastructure\UI\Web\Project\GetProjectFromIdTrait;
use App\Security\Application\Account\AccountFeaturesPolicy;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountFeature;
use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\ClickableInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/projects/{projectId}/credits/request', name: 'credit_purchases_request')]
class RequestController extends AbstractController
{
    use GetProjectFromIdTrait;

    private const string STEP_DATA = 'data';

    private const string STEP_CONFIRM = 'confirm';

    public function __invoke(
        string $projectId,
        ProjectRepository $projectRepository,
        RequestCreditPurchaseService $requestCreditPurchase,
        CreditPurchaseBillingDefaults $billingDefaults,
        CreditPurchaseQuoter $quoter,
        BillingCountries $billingCountries,
        LoggedUser $loggedUser,
        LoggedAccount $loggedAccount,
        AccountFeaturesPolicy $accountFeaturesPolicy,
        TranslatorInterface $translator,
        Request $request,
    ): Response {
        try {
            $project = $this->getProjectFromId($projectId, $projectRepository);

            if (ProjectType::EmissionCompensation !== $project->projectType()) {
                throw $this->createNotFoundException();
            }

            $account = $loggedAccount();

            if (null == $account) {
                throw $this->createAccessDeniedException($translator->trans('Debes pertenecer a una cuenta para poder acceder'));
            }

            if (!$accountFeaturesPolicy->isEnabled(AccountFeature::Compensation, $account->id())) {
                throw $this->createAccessDeniedException();
            }

            $countries = $billingCountries->countries();
            $defaults = $billingDefaults($account->id(), $loggedUser());

            $creditPurchaseRequest = new RequestCreditPurchaseRequest(
                accountId: $account->id(),
                projectId: $project->id(),
                tonnes: 0.0,
                legalName: $defaults->legalName,
                taxId: $defaults->taxId,
                street: $defaults->street,
                postalCode: $defaults->postalCode,
                city: $defaults->city,
                country: array_key_exists($defaults->country, $countries) ? $defaults->country : '',
                email: $defaults->email,
            );

            $form = $this->createForm(RequestCreditPurchaseType::class, $creditPurchaseRequest, ['countries' => $countries]);
            $form->handleRequest($request);

            $step = self::STEP_DATA;

            if ($form->isSubmitted()) {
                if ($this->isClicked($form, 'modify')) {
                    $step = self::STEP_DATA;
                } elseif (!$form->isValid()) {
                    if ($this->isClicked($form, 'request') && $this->onlyTermsAreInvalid($form)) {
                        $step = self::STEP_CONFIRM;
                        $form = $this->resetConfirmation($form, $creditPurchaseRequest, $countries);
                    }
                } elseif ($this->isClicked($form, 'calculate')) {
                    $step = self::STEP_CONFIRM;
                } elseif ($this->isClicked($form, 'request')) {
                    try {
                        $requestCreditPurchase($creditPurchaseRequest, $loggedUser());

                        $this->addFlash('success', new TranslatableMessage('Solicitud de créditos enviada correctamente'));

                        return $this->redirectToRoute('projects_view', ['projectId' => $project->id()]);
                    } catch (CreditPurchaseQuoteChangedException) {
                        $this->addFlash('warning', new TranslatableMessage('El importe ha cambiado desde que lo consultaste. Revísalo y confirma de nuevo.'));

                        $step = self::STEP_CONFIRM;
                        $form = $this->resetConfirmation($form, $creditPurchaseRequest, $countries);
                    } catch (\DomainException|\InvalidArgumentException|ProjectNotCompensableException) {
                        $this->addFlash('danger', new TranslatableMessage('Error inesperado'));

                        $step = self::STEP_DATA;
                    }
                }
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        $quote = self::STEP_CONFIRM === $step
            ? $quoter($project, $creditPurchaseRequest->tonnes, $creditPurchaseRequest->country, $creditPurchaseRequest->taxId)
            : null;

        return $this->render('@balance/credits/request.html.twig', [
            'project' => $project,
            'form' => $form->createView(),
            'step' => $step,
            'quote' => $quote,
            'countries' => $countries,
        ]);
    }

    /** @param FormInterface<mixed> $form */
    private function isClicked(FormInterface $form, string $button): bool
    {
        $child = $form->get($button);

        return $child instanceof ClickableInterface && $child->isClicked();
    }

    /** @param FormInterface<mixed> $form */
    private function onlyTermsAreInvalid(FormInterface $form): bool
    {
        $errors = iterator_to_array($form->getErrors(true));

        if ([] === $errors) {
            return false;
        }

        foreach ($errors as $error) {
            if ('termsAccepted' !== $error->getOrigin()?->getName()) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param FormInterface<mixed>  $form
     * @param array<string, string> $countries
     *
     * @return FormInterface<mixed>
     */
    private function resetConfirmation(FormInterface $form, RequestCreditPurchaseRequest $creditPurchaseRequest, array $countries): FormInterface
    {
        $termsErrors = $form->get('termsAccepted')->getErrors();

        $creditPurchaseRequest->termsAccepted = false;
        $creditPurchaseRequest->acceptedTotal = null;

        $form = $this->createForm(RequestCreditPurchaseType::class, $creditPurchaseRequest, ['countries' => $countries]);

        foreach ($termsErrors as $error) {
            $form->get('termsAccepted')->addError($error);
        }

        return $form;
    }
}
