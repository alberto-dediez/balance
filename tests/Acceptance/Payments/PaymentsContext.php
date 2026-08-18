<?php

declare(strict_types=1);

namespace App\Tests\Acceptance\Payments;

use App\Payments\Domain\Model\Payee\Payee;
use App\Payments\Domain\Model\Payee\PayeeId;
use App\Payments\Domain\Model\Payee\PayeeRepository;
use App\Payments\Domain\Model\Payee\PayeeStatus;
use App\Payments\Domain\Model\Payee\PayeeType;
use App\Payments\Domain\Model\Payout\Payout;
use App\Payments\Domain\Model\Payout\PayoutFilter;
use App\Payments\Domain\Model\Payout\PayoutRepository;
use App\Payments\Domain\Model\Payout\PayoutStatus;
use App\Shared\Domain\Model\Address;
use App\Tests\Acceptance\Shared\AcceptanceClient;
use Behat\Behat\Context\Context;
use Behat\Step\Given;
use Behat\Step\Then;
use Behat\Step\When;
use PHPUnit\Framework\Assert;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

final class PaymentsContext implements Context
{
    private readonly AcceptanceClient $client;

    public function __construct(KernelBrowser $client)
    {
        $this->client = AcceptanceClient::instance($client);
    }

    public function getPayeeRepository(): PayeeRepository
    {
        return $this->client->getContainer()->get(PayeeRepository::class);
    }

    #[Given('payee :legalName with id :id for :type :subjectId exists')]
    public function payeeExists(string $legalName, string $id, string $type, string $subjectId): void
    {
        $this->registerPayee($id, $legalName, $type, $subjectId, 'ES9121000418450200051332', 'Titular');
    }

    #[Given('incomplete payee :legalName with id :id for :type :subjectId exists')]
    public function incompletePayeeExists(string $legalName, string $id, string $type, string $subjectId): void
    {
        $this->registerPayee($id, $legalName, $type, $subjectId, null, null);
    }

    #[Given('blocked payee :legalName with id :id for :type :subjectId exists')]
    public function blockedPayeeExists(string $legalName, string $id, string $type, string $subjectId): void
    {
        $this->registerPayee($id, $legalName, $type, $subjectId, 'ES9121000418450200051332', 'Titular');

        $payee = $this->getPayeeRepository()->find(new PayeeId($id));

        Assert::assertInstanceOf(Payee::class, $payee);

        $payee->block();

        $this->getPayeeRepository()->add($payee);
    }

    #[Then('payee :id should be :status')]
    public function payeeShouldBe(string $id, string $status): void
    {
        $payee = $this->getPayeeRepository()->find(new PayeeId($id));

        Assert::assertInstanceOf(Payee::class, $payee);
        Assert::assertSame(PayeeStatus::from($status), $payee->status());
    }

    #[Then('the payee of :type :subjectId should be :status')]
    public function thePayeeOfSubjectShouldBe(string $type, string $subjectId, string $status): void
    {
        $payee = $this->getPayeeRepository()->findOneBySubject(PayeeType::from($type), $subjectId);

        Assert::assertInstanceOf(Payee::class, $payee);
        Assert::assertSame(PayeeStatus::from($status), $payee->status());
    }

    private function registerPayee(
        string $id,
        string $legalName,
        string $type,
        string $subjectId,
        ?string $iban,
        ?string $holderName,
    ): void {
        $payee = new Payee(id: new PayeeId($id));

        $payee->register(
            type: PayeeType::from($type),
            subjectId: $subjectId,
            legalName: $legalName,
            taxId: 'B12345678',
            address: new Address(street: 'Calle Mayor 1', postalCode: '28001', city: 'Madrid', country: 'ES'),
            iban: $iban,
            bic: null,
            holderName: $holderName,
            notes: null,
        );

        $this->getPayeeRepository()->add($payee);
    }

    public function getPayoutRepository(): PayoutRepository
    {
        return $this->client->getContainer()->get(PayoutRepository::class);
    }

    #[Then('there should be :count payouts')]
    public function thereShouldBePayouts(int $count): void
    {
        Assert::assertCount($count, $this->getPayoutRepository()->findByFilter(new PayoutFilter(limit: 0)));
    }

    #[Then('the only payout should be :status of :amount')]
    public function theOnlyPayoutShouldBe(string $status, string $amount): void
    {
        $payouts = $this->getPayoutRepository()->findByFilter(new PayoutFilter(limit: 0));

        Assert::assertCount(1, $payouts);
        Assert::assertSame(PayoutStatus::from($status), $payouts[0]->status());
        Assert::assertEqualsWithDelta((float) $amount, $payouts[0]->payableAmount(), 0.01);
    }

    #[Then('the only payout should be numbered :number')]
    public function theOnlyPayoutShouldBeNumbered(string $number): void
    {
        $payouts = $this->getPayoutRepository()->findByFilter(new PayoutFilter(limit: 0));

        Assert::assertCount(1, $payouts);
        Assert::assertSame($number, $payouts[0]->number());
    }

    #[Then('I go to the only payout page :suffix')]
    public function iGoToTheOnlyPayoutPage(string $suffix): void
    {
        $payouts = $this->getPayoutRepository()->findByFilter(new PayoutFilter(limit: 0));

        Assert::assertCount(1, $payouts);
        Assert::assertInstanceOf(Payout::class, $payouts[0]);

        $this->client->request('GET', sprintf('/es/admin/payouts/%s%s', (string) $payouts[0]->id(), $suffix));
    }

    #[When('I cancel the only payout')]
    public function iCancelTheOnlyPayout(): void
    {
        $payouts = $this->getPayoutRepository()->findByFilter(new PayoutFilter(limit: 0));

        Assert::assertCount(1, $payouts);

        $this->client->request('GET', '/es/admin/payouts');

        $links = $this->client->getCrawler()
            ->filter(sprintf('a[href^="/es/admin/payouts/%s/cancel"]', (string) $payouts[0]->id()))
            ->extract(['href'])
        ;

        Assert::assertNotSame([], $links);

        $this->client->request('GET', $links[0]);
    }

    #[When('I go to the only payout statement as a regular user')]
    public function iGoToTheOnlyPayoutStatement(): void
    {
        $payouts = $this->getPayoutRepository()->findByFilter(new PayoutFilter(limit: 0));

        Assert::assertCount(1, $payouts);

        $this->client->request('GET', sprintf('/es/payouts/%s/statement', (string) $payouts[0]->id()));
    }
}
