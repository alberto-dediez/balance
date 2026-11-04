<?php

declare(strict_types=1);

namespace App\Property\Infrastructure\UI\Web\DistributorAssignment;

use App\Property\Application\DistributorAssignment\Remove\RemoveDistributorAssignmentRequest;
use App\Property\Application\DistributorAssignment\Remove\RemoveDistributorAssignmentService;
use App\Property\Domain\Model\DistributorAssignment\DistributorAssignmentId;
use App\Property\Domain\Model\DistributorAssignment\DistributorAssignmentRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountRepository;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CsrfProtected;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/distributors/remove/{id}', name: 'distributors_remove')]
#[CsrfProtected('remove')]
class RemoveController extends AbstractController
{
    public function __invoke(
        string $id,
        DistributorAssignmentRepository $distributorAssignmentRepository,
        AccountRepository $accountRepository,
        RemoveDistributorAssignmentService $removeDistributorAssignment,
        LoggedUser $loggedUser,
    ): Response {
        $assignment = $distributorAssignmentRepository->find(new DistributorAssignmentId($id));

        if (null === $assignment) {
            throw $this->createNotFoundException('Distributor assignment not found');
        }

        $distributorAccountId = $assignment->distributorAccountId();
        $distributor = null !== $distributorAccountId ? ($accountRepository->find($distributorAccountId)?->name() ?? (string) $distributorAccountId) : '';

        try {
            $removeDistributorAssignment(new RemoveDistributorAssignmentRequest($assignment->id()), $loggedUser());

            $this->addFlash('success', new TranslatableMessage('Se ha retirado el distribuidor {distributor}', ['{distributor}' => $distributor]));
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        } catch (\Exception) {
            $this->addFlash('danger', new TranslatableMessage('No se ha podido retirar el distribuidor {distributor}', ['{distributor}' => $distributor]));
        }

        return $this->redirectToRoute('distributors');
    }
}
