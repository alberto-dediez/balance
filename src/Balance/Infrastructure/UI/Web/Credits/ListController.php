<?php

declare(strict_types=1);

namespace App\Balance\Infrastructure\UI\Web\Credits;

use App\Balance\Application\CreditPurchase\Balance\ListAccountCreditsRequest;
use App\Balance\Application\CreditPurchase\Balance\ListAccountCreditsService;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/credits', name: 'credits')]
class ListController extends AbstractController
{
    public function __invoke(
        ListAccountCreditsService $listAccountCredits,
        LoggedUser $loggedUser,
        LoggedAccount $loggedAccount,
        TranslatorInterface $translator,
    ): Response {
        $account = $loggedAccount();

        if (null == $account) {
            throw $this->createAccessDeniedException($translator->trans('Debes pertenecer a una cuenta para poder acceder'));
        }

        try {
            $response = $listAccountCredits(new ListAccountCreditsRequest($account->id()), $loggedUser());
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@balance/credits/list.html.twig', [
            'balances' => $response->balances,
        ]);
    }
}
