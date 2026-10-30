<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\PropertyCarbonFootprintCertificate;

use App\CarbonFootprint\Application\PropertyCarbonFootprintCertificate\Issue\IssuePropertyCarbonFootprintCertificateRequest;
use App\CarbonFootprint\Application\PropertyCarbonFootprintCertificate\Issue\IssuePropertyCarbonFootprintCertificateService;
use App\CarbonFootprint\Application\PropertyCarbonFootprintCertificate\PropertyCarbonFootprintAlreadyCertifiedException;
use App\CarbonFootprint\Application\PropertyCarbonFootprintCertificate\PropertyCarbonFootprintNotCertifiableException;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintRepository;
use App\CarbonFootprint\Infrastructure\Symfony\Form\IssuePropertyCarbonFootprintCertificateType;
use App\CarbonFootprint\Infrastructure\UI\Web\PropertyCarbonFootprint\GetPropertyCarbonFootprintFromIdTrait;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/property-carbon-footprints/{propertyCarbonFootprintId}/certificate/issue', name: 'property_carbon_footprints_certificate_issue', methods: ['POST'])]
class IssueController extends AbstractController
{
    use GetPropertyCarbonFootprintFromIdTrait;

    public function __invoke(
        string $propertyCarbonFootprintId,
        PropertyCarbonFootprintRepository $propertyCarbonFootprintRepository,
        IssuePropertyCarbonFootprintCertificateService $issueCertificate,
        LoggedUser $loggedUser,
        Request $request,
    ): Response {
        $propertyCarbonFootprint = $this->getPropertyCarbonFootprintFromId($propertyCarbonFootprintId, $propertyCarbonFootprintRepository);

        $this->denyAccessUnlessGranted(SecurityAction::Edit->value, $propertyCarbonFootprint);

        $issueRequest = new IssuePropertyCarbonFootprintCertificateRequest($propertyCarbonFootprint->id());

        $form = $this->createForm(IssuePropertyCarbonFootprintCertificateType::class, $issueRequest);
        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            $errors = $form->getErrors(true);

            $this->addFlash('danger', count($errors) > 0 ? $errors->current()->getMessage() : new TranslatableMessage('No se ha podido emitir el certificado de huella.'));

            return $this->redirectToResults($propertyCarbonFootprintId);
        }

        try {
            $issueCertificate($issueRequest, $loggedUser());

            $this->addFlash('success', new TranslatableMessage('Certificado de huella emitido. El PDF estará disponible en unos minutos.'));
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        } catch (PropertyCarbonFootprintAlreadyCertifiedException) {
            $this->addFlash('danger', new TranslatableMessage('La huella ya tiene un certificado vigente.'));
        } catch (PropertyCarbonFootprintNotCertifiableException) {
            $this->addFlash('danger', new TranslatableMessage('La huella debe estar completada y su informe generado para emitir el certificado.'));
        }

        return $this->redirectToResults($propertyCarbonFootprintId);
    }

    private function redirectToResults(string $propertyCarbonFootprintId): Response
    {
        return $this->redirectToRoute('property_carbon_footprints_results', ['propertyCarbonFootprintId' => $propertyCarbonFootprintId]);
    }
}
