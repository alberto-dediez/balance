<?php

declare(strict_types=1);

namespace App\Materiality\Infrastructure\UI\Web\Admin\Seed;

use App\Materiality\Application\MaterialityTopic\MaterialityTopicAuthorizer;
use App\Materiality\Application\Seed\SeedStandardMaterialityMastersRequest;
use App\Materiality\Application\Seed\SeedStandardMaterialityMastersService;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopic;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicId;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/admin/materiality/seed', name: 'admin_materiality_seed', methods: ['GET'])]
class SeedController extends AbstractController
{
    public function __invoke(
        MaterialityTopicAuthorizer $authorizer,
        SeedStandardMaterialityMastersService $seedStandardMaterialityMasters,
        LoggedUser $loggedUser,
        Request $request,
    ): Response {
        try {
            $authorizer->denyUnlessCanDoThat(
                new MaterialityTopic(id: MaterialityTopicId::generate()),
                SecurityAction::Admin,
                $loggedUser(),
            );
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        if (!$this->isCsrfTokenValid('materiality_seed', $request->query->getString('_token'))) {
            throw $this->createAccessDeniedException('Token CSRF no válido');
        }

        $response = $seedStandardMaterialityMasters(new SeedStandardMaterialityMastersRequest(), $loggedUser());

        $topics = count($response->registeredTopics);
        $questions = count($response->registeredQuestions);

        if (0 === $topics && 0 === $questions) {
            $this->addFlash('success', new TranslatableMessage('Los maestros estándar ya estaban registrados'));
        } else {
            $this->addFlash('success', new TranslatableMessage(
                'Se han registrado {topics} categorías y {questions} preguntas estándar',
                ['{topics}' => $topics, '{questions}' => $questions],
            ));
        }

        return $this->redirectToRoute('admin_materiality_topics');
    }
}
