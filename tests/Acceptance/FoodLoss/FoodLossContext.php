<?php

declare(strict_types=1);

namespace App\Tests\Acceptance\FoodLoss;

use App\FoodLoss\Domain\Model\Action\ActionRepository;
use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPointRepository;
use App\FoodLoss\Domain\Model\DeliveryLog\DeliveryLogRepository;
use App\FoodLoss\Domain\Model\DoggyBagException\DoggyBagExceptionRepository;
use App\FoodLoss\Domain\Model\DoggyBagSign\DoggyBagSignRepository;
use App\FoodLoss\Domain\Model\DonationAgreement\DonationAgreementRepository;
use App\FoodLoss\Domain\Model\Incidence\IncidenceEvidenceId;
use App\FoodLoss\Domain\Model\Incidence\IncidenceId;
use App\FoodLoss\Domain\Model\Incidence\IncidenceRepository;
use App\FoodLoss\Domain\Model\Packaging\PackagingRepository;
use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanId;
use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanRepository;
use App\FoodLoss\Domain\Model\Protocol\ProtocolRepository;
use App\FoodLoss\Domain\Model\ProtocolAccess\ProtocolAccessRepository;
use App\FoodLoss\Domain\Model\Reason\ReasonRepository;
use App\FoodLoss\Domain\Model\RecipientOrganization\RecipientOrganizationId;
use App\FoodLoss\Domain\Model\RecipientOrganization\RecipientOrganizationRepository;
use App\FoodLoss\Domain\Model\ServiceCategory\ServiceCategoryRepository;
use App\FoodLoss\Domain\Model\Surplus\SurplusRepository;
use App\FoodLoss\Domain\Model\SurplusCause\SurplusCauseRepository;
use App\FoodLoss\Domain\Model\TrainingAttendance\TrainingAttendanceId;
use App\FoodLoss\Domain\Model\TrainingAttendance\TrainingAttendanceRepository;
use App\FoodLoss\Domain\Model\TrainingSession\TrainingSessionId;
use App\FoodLoss\Domain\Model\TrainingSession\TrainingSessionRepository;
use App\HumanResources\Domain\Model\Employee\EmployeeId;
use App\HumanResources\Domain\Model\Employee\EmployeeRepository;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\UserId;
use App\Tests\Acceptance\Shared\AcceptanceClient;
use App\Tests\Unit\FoodLoss\Domain\Model\Action\ActionMother;
use App\Tests\Unit\FoodLoss\Domain\Model\CriticalPoint\CriticalPointMother;
use App\Tests\Unit\FoodLoss\Domain\Model\DeliveryLog\DeliveryLogMother;
use App\Tests\Unit\FoodLoss\Domain\Model\DoggyBagException\DoggyBagExceptionMother;
use App\Tests\Unit\FoodLoss\Domain\Model\DoggyBagSign\DoggyBagSignMother;
use App\Tests\Unit\FoodLoss\Domain\Model\DonationAgreement\DonationAgreementMother;
use App\Tests\Unit\FoodLoss\Domain\Model\Incidence\IncidenceMother;
use App\Tests\Unit\FoodLoss\Domain\Model\Packaging\PackagingMother;
use App\Tests\Unit\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanMother;
use App\Tests\Unit\FoodLoss\Domain\Model\Protocol\ProtocolMother;
use App\Tests\Unit\FoodLoss\Domain\Model\ProtocolAccess\ProtocolAccessMother;
use App\Tests\Unit\FoodLoss\Domain\Model\Reason\ReasonMother;
use App\Tests\Unit\FoodLoss\Domain\Model\RecipientOrganization\RecipientOrganizationMother;
use App\Tests\Unit\FoodLoss\Domain\Model\ServiceCategory\ServiceCategoryMother;
use App\Tests\Unit\FoodLoss\Domain\Model\Surplus\SurplusMother;
use App\Tests\Unit\FoodLoss\Domain\Model\SurplusCause\SurplusCauseMother;
use App\Tests\Unit\FoodLoss\Domain\Model\TrainingAttendance\TrainingAttendanceMother;
use App\Tests\Unit\FoodLoss\Domain\Model\TrainingSession\TrainingSessionMother;
use App\Tests\Unit\HumanResources\Domain\Model\Employee\EmployeeMother;
use Behat\Behat\Context\Context;
use Behat\Step\Given;
use Behat\Step\Then;
use Behat\Step\When;
use PHPUnit\Framework\Assert;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final readonly class FoodLossContext implements Context
{
    private AcceptanceClient $client;

    public function __construct(
        KernelBrowser $kernelBrowser,
    ) {
        $this->client = AcceptanceClient::instance($kernelBrowser);
    }

    public function getActionRepository(): ActionRepository
    {
        return $this->client->getContainer()->get(ActionRepository::class);
    }

    public function getServiceCategoryRepository(): ServiceCategoryRepository
    {
        return $this->client->getContainer()->get(ServiceCategoryRepository::class);
    }

    public function getCriticalPointRepository(): CriticalPointRepository
    {
        return $this->client->getContainer()->get(CriticalPointRepository::class);
    }

    public function getReasonRepository(): ReasonRepository
    {
        return $this->client->getContainer()->get(ReasonRepository::class);
    }

    public function getRecipientOrganizationRepository(): RecipientOrganizationRepository
    {
        return $this->client->getContainer()->get(RecipientOrganizationRepository::class);
    }

    public function getDeliveryLogRepository(): DeliveryLogRepository
    {
        return $this->client->getContainer()->get(DeliveryLogRepository::class);
    }

    public function getDonationAgreementRepository(): DonationAgreementRepository
    {
        return $this->client->getContainer()->get(DonationAgreementRepository::class);
    }

    public function getPreventionPlanRepository(): PreventionPlanRepository
    {
        return $this->client->getContainer()->get(PreventionPlanRepository::class);
    }

    public function getPackagingRepository(): PackagingRepository
    {
        return $this->client->getContainer()->get(PackagingRepository::class);
    }

    public function getProtocolRepository(): ProtocolRepository
    {
        return $this->client->getContainer()->get(ProtocolRepository::class);
    }

    public function getProtocolAccessRepository(): ProtocolAccessRepository
    {
        return $this->client->getContainer()->get(ProtocolAccessRepository::class);
    }

    public function getEmployeeRepository(): EmployeeRepository
    {
        return $this->client->getContainer()->get(EmployeeRepository::class);
    }

    public function getDoggyBagSignRepository(): DoggyBagSignRepository
    {
        return $this->client->getContainer()->get(DoggyBagSignRepository::class);
    }

    public function getDoggyBagExceptionRepository(): DoggyBagExceptionRepository
    {
        return $this->client->getContainer()->get(DoggyBagExceptionRepository::class);
    }

    public function getIncidenceRepository(): IncidenceRepository
    {
        return $this->client->getContainer()->get(IncidenceRepository::class);
    }

    public function getTrainingSessionRepository(): TrainingSessionRepository
    {
        return $this->client->getContainer()->get(TrainingSessionRepository::class);
    }

    public function getTrainingAttendanceRepository(): TrainingAttendanceRepository
    {
        return $this->client->getContainer()->get(TrainingAttendanceRepository::class);
    }

    public function getSurplusCauseRepository(): SurplusCauseRepository
    {
        return $this->client->getContainer()->get(SurplusCauseRepository::class);
    }

    public function getSurplusRepository(): SurplusRepository
    {
        return $this->client->getContainer()->get(SurplusRepository::class);
    }

    #[Given('surplus with id :id of account :accountId exists')]
    public function surplusWithIdOfAccountExists(string $id, string $accountId): void
    {
        $surplus = SurplusMother::fromIdAndAccount($id, $accountId);

        $this->getSurplusRepository()->add($surplus);
    }

    #[Given('action :description with id :id exists')]
    public function actionWithIdExists(string $description, string $id): void
    {
        $action = ActionMother::fromDescriptionAndId($description, $id);

        $this->getActionRepository()->add($action);
    }

    #[Given('service category :name with id :id exists')]
    public function serviceCategoryWithIdExists(string $name, string $id): void
    {
        $serviceCategory = ServiceCategoryMother::fromNameAndId($name, $id);

        $this->getServiceCategoryRepository()->add($serviceCategory);
    }

    #[Given('critical point :name with id :id exists')]
    public function criticalPointWithIdExists(string $name, string $id): void
    {
        $criticalPoint = CriticalPointMother::fromNameAndId($name, $id);

        $this->getCriticalPointRepository()->add($criticalPoint);
    }

    #[Given('critical point :name with id :id of account :accountId exists')]
    public function criticalPointWithIdOfAccountExists(string $name, string $id, string $accountId): void
    {
        $criticalPoint = CriticalPointMother::from(
            name: $name,
            id: $id,
            accountId: new AccountId($accountId),
        );

        $this->getCriticalPointRepository()->add($criticalPoint);
    }

    #[Given('reason :description with id :id exists')]
    public function reasonWithIdExists(string $description, string $id): void
    {
        $reason = ReasonMother::fromDescriptionAndId($description, $id);

        $this->getReasonRepository()->add($reason);
    }

    #[Given('critical point :name with id :id of account :accountId and property :propertyId exists')]
    public function criticalPointWithIdOfAccountAndPropertyExists(string $name, string $id, string $accountId, string $propertyId): void
    {
        $criticalPoint = CriticalPointMother::from(
            name: $name,
            id: $id,
            accountId: new AccountId($accountId),
            propertyId: new PropertyId($propertyId),
        );

        $this->getCriticalPointRepository()->add($criticalPoint);
    }

    #[Given('recipient organization :name with id :id exists')]
    public function recipientOrganizationWithIdExists(string $name, string $id): void
    {
        $recipientOrganization = RecipientOrganizationMother::fromNameAndId($name, $id);

        $this->getRecipientOrganizationRepository()->add($recipientOrganization);
    }

    #[Given('recipient organization :name with id :id of account :accountId exists')]
    public function recipientOrganizationWithIdOfAccountExists(string $name, string $id, string $accountId): void
    {
        $recipientOrganization = RecipientOrganizationMother::from(
            name: $name,
            id: $id,
            accountId: $accountId,
        );

        $this->getRecipientOrganizationRepository()->add($recipientOrganization);
    }

    #[Given('delivery log :productType with id :id of account :accountId exists')]
    public function deliveryLogWithIdOfAccountExists(string $productType, string $id, string $accountId): void
    {
        $deliveryLog = DeliveryLogMother::from(
            productType: $productType,
            id: $id,
            accountId: $accountId,
        );

        $this->getDeliveryLogRepository()->add($deliveryLog);
    }

    #[Given('donation agreement with id :id exists')]
    public function donationAgreementWithIdExists(string $id): void
    {
        $donationAgreement = DonationAgreementMother::from(
            id: $id,
            recipientOrganizationId: (string) RecipientOrganizationId::generate(),
            accountId: 'account-test',
            startDate: new \DateTimeImmutable('2023-01-01'),
            expirationDate: new \DateTimeImmutable('2024-01-01'),
            uploadedBy: (string) UserId::generate(),
            path: 'path/test.pdf',
        );

        $this->getDonationAgreementRepository()->add($donationAgreement);
    }

    #[Given('donation agreement with id :id of account :accountId exists')]
    public function donationAgreementWithIdOfAccountExists(string $id, string $accountId): void
    {
        $donationAgreement = DonationAgreementMother::from(
            id: $id,
            recipientOrganizationId: (string) RecipientOrganizationId::generate(),
            accountId: $accountId,
            startDate: new \DateTimeImmutable('2023-01-01'),
            expirationDate: new \DateTimeImmutable('2024-01-01'),
            uploadedBy: (string) UserId::generate(),
            path: 'path/test.pdf',
        );

        $this->getDonationAgreementRepository()->add($donationAgreement);
    }

    #[Given('prevention plan :name with id :id exists')]
    public function preventionPlanWithIdExists(string $name, string $id): void
    {
        $preventionPlan = PreventionPlanMother::fromNameAndId($name, $id);

        $this->getPreventionPlanRepository()->add($preventionPlan);
    }

    #[Given('prevention plan :id is mark as validated')]
    public function preventionPlanIsMarkAsValidated(string $id): void
    {
        $preventionPlan = $this->getPreventionPlanRepository()->find(new PreventionPlanId($id));

        assert(null !== $preventionPlan);

        $preventionPlan->markAsValidated();
    }

    #[Given('prevention plan :name with id :id of account :accountId exists')]
    public function preventionPlanWithIdOfAccountExists(string $name, string $id, string $accountId): void
    {
        $preventionPlan = PreventionPlanMother::from(
            name: $name,
            id: $id,
            accountId: $accountId,
        );

        $this->getPreventionPlanRepository()->add($preventionPlan);
    }

    #[Given('prevention plan :name with id :id of account :accountId with property :propertyId exists')]
    public function preventionPlanWithIdOfAccountAndPropertyExists(string $name, string $id, string $accountId, string $propertyId): void
    {
        $preventionPlan = PreventionPlanMother::fromWithProperty($name, $id, $accountId, $propertyId);

        $this->getPreventionPlanRepository()->add($preventionPlan);
    }

    #[Given('prevention plan :name with id :id of account :accountId with generated sections exists')]
    public function preventionPlanWithGeneratedSectionsExists(string $name, string $id, string $accountId): void
    {
        $preventionPlan = PreventionPlanMother::withGeneratedSections($name, $id, $accountId);

        $this->getPreventionPlanRepository()->add($preventionPlan);
    }

    #[Given('prevention plan :name with id :id of account :accountId in configured state exists')]
    public function preventionPlanInConfiguredStateExists(string $name, string $id, string $accountId): void
    {
        $preventionPlan = PreventionPlanMother::withConfiguredStatus($name, $id, $accountId);

        $this->getPreventionPlanRepository()->add($preventionPlan);
    }

    #[Given('prevention plan :name with id :id of account :accountId with draft approved status exists')]
    public function preventionPlanWithDraftApprovedStatusExists(string $name, string $id, string $accountId): void
    {
        $preventionPlan = PreventionPlanMother::withDraftApprovedStatus($name, $id, $accountId);

        $this->getPreventionPlanRepository()->add($preventionPlan);
    }

    #[Given('packaging :name with id :id of account :accountId exists')]
    public function packagingWithIdOfAccountExists(string $name, string $id, string $accountId): void
    {
        $packaging = PackagingMother::from(
            name: $name,
            id: $id,
            accountId: $accountId,
        );

        $this->getPackagingRepository()->add($packaging);
    }

    #[Given('a dummy PDF file exists in :path')]
    public function aDummyPdfFileExistsIn(string $path): void
    {
        $content = "%PDF-1.4\n1 0 obj\n<< /Title (Test) >>\nendobj\nxref\n0 1\n0000000000 65535 f\ntrailer\n<< /Root 1 0 R >>\n%%EOF";
        file_put_contents($path, $content);
    }

    #[Given('protocol :name with id :id of account :accountId exists')]
    public function protocolWithIdOfAccountExists(string $name, string $id, string $accountId): void
    {
        $protocol = ProtocolMother::from(
            name: $name,
            id: $id,
            accountId: $accountId,
        );

        $this->getProtocolRepository()->add($protocol);
    }

    #[Given('doggy bag sign :name with id :id of account :accountId exists')]
    public function doggyBagSignWithIdOfAccountExists(string $name, string $id, string $accountId): void
    {
        $doggyBagSign = DoggyBagSignMother::from(
            name: $name,
            id: $id,
            accountId: $accountId,
        );

        $this->getDoggyBagSignRepository()->add($doggyBagSign);
    }

    #[Given('doggy bag exception :description with id :id of account :accountId exists')]
    public function doggyBagExceptionWithIdOfAccountExists(string $description, string $id, string $accountId): void
    {
        $doggyBagException = DoggyBagExceptionMother::from(
            description: $description,
            criticalPointId: (string) \App\FoodLoss\Domain\Model\CriticalPoint\CriticalPointId::generate(),
            accountId: $accountId,
            id: $id,
        );

        $this->getDoggyBagExceptionRepository()->add($doggyBagException);
    }

    #[Given('incidence :name with id :id of account :accountId exists')]
    public function incidenceWithIdOfAccountExists(string $name, string $id, string $accountId): void
    {
        $incidence = IncidenceMother::from(
            name: $name,
            id: $id,
            accountId: $accountId,
        );

        $this->getIncidenceRepository()->add($incidence);
    }

    #[Given('training session :title with id :id of account :accountId exists')]
    public function trainingSessionWithIdOfAccountExists(string $title, string $id, string $accountId): void
    {
        $trainingSession = TrainingSessionMother::from(
            title: $title,
            id: $id,
            accountId: $accountId,
        );

        $this->getTrainingSessionRepository()->add($trainingSession);
    }

    #[Given('surplus cause :name with id :id exists')]
    public function surplusCauseWithIdExists(string $name, string $id): void
    {
        $surplusCause = SurplusCauseMother::fromNameAndId($name, $id);

        $this->getSurplusCauseRepository()->add($surplusCause);
    }

    #[Given('incidence :name with id :id of account :accountId with evidence :evidenceId at :filePath exists')]
    public function incidenceWithEvidenceAtPathExists(
        string $name,
        string $id,
        string $accountId,
        string $evidenceId,
        string $filePath,
    ): void {
        $incidence = IncidenceMother::from(
            name: $name,
            id: $id,
            accountId: $accountId,
        );

        $incidence->addEvidence(
            evidenceId: new IncidenceEvidenceId($evidenceId),
            path: $filePath,
            originalName: basename($filePath),
            mimeType: 'application/pdf',
        );

        $this->getIncidenceRepository()->add($incidence);

        $uploadsDir = (string) $this->client->getContainer()->getParameter('app.uploads_dir');
        $absolutePath = $uploadsDir . '/' . $filePath;
        @mkdir(dirname($absolutePath), 0777, true);
        file_put_contents($absolutePath, "%PDF-1.4\n%%EOF");
    }

    #[Then('incidence :id should have :count evidences')]
    public function incidenceShouldHaveNEvidences(string $id, int $count): void
    {
        $incidence = $this->getIncidenceRepository()->find(new IncidenceId($id));
        Assert::assertNotNull($incidence);
        Assert::assertCount($count, $incidence->evidences()->toArray());
    }

    #[When('I submit the form with :data and button :button adding evidence file :filePath')]
    public function iSubmitFormWithEvidenceFile(string $data, string $button, string $filePath): void
    {
        $crawler = $this->client->getCrawler();
        $buttonNode = $crawler->selectButton($button);
        $form = $buttonNode->form();
        $formName = (string) $crawler->filter('form')->attr('name');
        $action = $form->getUri();

        $values = $form->getPhpValues();

        foreach (explode('|', $data) as $argument) {
            if ('' === $argument) {
                continue;
            }
            [$fieldName, $value] = explode(':', $argument, 2);
            $values[$formName][$fieldName] = $value;
        }

        $currentEvidences = $values[$formName]['evidences'] ?? [];
        $nextIndex = count($currentEvidences);
        $values[$formName]['evidences'][$nextIndex] = ['id' => ''];

        $projectDir = (string) $this->client->getContainer()->getParameter('kernel.project_dir');
        $absoluteFilePath = $projectDir . '/' . $filePath;

        $files = [
            $formName => [
                'evidences' => [
                    $nextIndex => [
                        'file' => new UploadedFile($absoluteFilePath, basename($filePath), null, null, true),
                    ],
                ],
            ],
        ];

        $this->client->getKernelBrowser()->request('POST', $action, $values, $files);
    }

    #[Given('employee :name with id :id of account :accountId exists')]
    public function employeeWithIdOfAccountExists(string $name, string $id, string $accountId): void
    {
        $employee = EmployeeMother::from(
            name: $name,
            email: strtolower($name) . '@test.com',
            id: $id,
            accountId: $accountId,
        );

        $this->getEmployeeRepository()->add($employee);
    }

    #[Given('protocol access for employee :employeeId of protocol :protocolId of account :accountId exists')]
    public function protocolAccessForEmployeeOfProtocolExists(string $employeeId, string $protocolId, string $accountId): void
    {
        $access = ProtocolAccessMother::forProtocolAndEmployee($protocolId, $employeeId, $accountId);

        $this->getProtocolAccessRepository()->add($access);
    }

    #[Given('training attendance for employee :employeeId of session :sessionId of account :accountId exists')]
    public function trainingAttendanceForEmployeeOfSessionExists(string $employeeId, string $sessionId, string $accountId): void
    {
        $attendance = TrainingAttendanceMother::forSessionAndEmployee(
            $sessionId,
            $employeeId,
            $accountId,
        );

        $this->getTrainingAttendanceRepository()->add($attendance);
    }

    #[Given('training attendance :attendanceId for employee :employeeId of session :sessionId of account :accountId at :filePath exists')]
    public function trainingAttendanceWithIdExists(string $attendanceId, string $employeeId, string $sessionId, string $accountId, string $filePath): void
    {
        $attendance = new \App\FoodLoss\Domain\Model\TrainingAttendance\TrainingAttendance(
            id: new TrainingAttendanceId($attendanceId),
        );
        $attendance->register(
            trainingSessionId: new TrainingSessionId($sessionId),
            employeeId: new EmployeeId($employeeId),
            accountId: new AccountId($accountId),
            attendedAt: new \DateTimeImmutable('2024-03-15'),
            filePath: $filePath,
        );

        $this->getTrainingAttendanceRepository()->add($attendance);
    }

    #[When('I submit the form with :data and button :button removing all evidences')]
    public function iSubmitFormRemovingAllEvidences(string $data, string $button): void
    {
        $crawler = $this->client->getCrawler();
        $buttonNode = $crawler->selectButton($button);
        $form = $buttonNode->form();
        $formName = (string) $crawler->filter('form')->attr('name');
        $action = $form->getUri();

        $values = $form->getPhpValues();

        foreach (explode('|', $data) as $argument) {
            if ('' === $argument) {
                continue;
            }
            [$fieldName, $value] = explode(':', $argument, 2);
            $values[$formName][$fieldName] = $value;
        }

        $values[$formName]['evidences'] = [];

        $this->client->getKernelBrowser()->request('POST', $action, $values, []);
    }
}
