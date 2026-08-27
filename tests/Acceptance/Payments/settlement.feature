Feature:
    Ability for admins to settle what the ledger owes to a payee and to pay it

    Background:
        Given user "payment" with role "ROLE_ADMIN" exists
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given european union country "España" with iso "ESP" and vat "21" and id "C-ES" exists
        Given project "Reforestación" with id "1111" and price per tonne "20" exists
        Given project "1111" is adhered to account "666"
        Given credit purchase "2222" for account "666" and project "1111" requesting "10" tonnes exists
        Given credit purchase "2222" is validated with "10" tonnes and reference "REF-1" paid by "paid_by_client"
        Given contribution "C1" of account "666" for project "1111" with "2" tonnes is pending until "+12 hours"
        When the gateway notifies that the payment of contribution "C1" succeeded

    Scenario: Settling an account with a ready payee creates a payout for its pending balance
        Given payee "Hotel X SL" with id "P1" for "account" "666" exists
        When I am logged as "admin@dediez.software"
        When I settle the account "666"
        When I follow redirect
        Then the response status should be 200
        Then the only payout should be "draft" of "30.00"
        Then the ledger balance of account "666" should be "30.00"

    Scenario: Settling twice leaves nothing left to settle
        Given payee "Hotel X SL" with id "P1" for "account" "666" exists
        When I am logged as "admin@dediez.software"
        When I settle the account "666"
        When I settle the account "666"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "No hay asientos pendientes de liquidar"
        Then there should be 1 payouts

    Scenario: An account without a ready payee is not offered for settlement
        Given incomplete payee "Hotel X SL" with id "P1" for "account" "666" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/treasury"
        Then the response status should be 200
        Then I see a text "su ficha de cobro no está lista"
        Then I do not see a text "Crear liquidación"
        Then there should be 0 payouts

    Scenario: Settling without a CSRF token is refused
        Given payee "Hotel X SL" with id "P1" for "account" "666" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/treasury/settle/account/666"
        Then the response status should be 403
        Then there should be 0 payouts

    Scenario: Marking a payout as paid records its transfer reference
        Given payee "Hotel X SL" with id "P1" for "account" "666" exists
        When I am logged as "admin@dediez.software"
        When I settle the account "666"
        When I go to the only payout page "/pay"
        Then the response status should be 200
        When I submit a form with "reference:TRF-2026-001" and button "Marcar como pagada"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Liquidación marcada como pagada"
        Then the only payout should be "paid" of "30.00"

    Scenario: Cancelling a payout releases its ledger entries
        Given payee "Hotel X SL" with id "P1" for "account" "666" exists
        When I am logged as "admin@dediez.software"
        When I settle the account "666"
        When I cancel the only payout
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Liquidación cancelada"
        Then the only payout should be "cancelled" of "30.00"
        When I settle the account "666"
        When I follow redirect
        Then there should be 2 payouts

    Scenario: The detail of a payout lists the entries it settles
        Given payee "Hotel X SL" with id "P1" for "account" "666" exists
        When I am logged as "admin@dediez.software"
        When I settle the account "666"
        When I go to the only payout page ""
        Then the response status should be 200
        Then I see a text "Hotel X SL"
        Then I see a text "REF-1"
        Then I see a text "Reforestación"

    Scenario: As an owner I cannot settle nor see the payouts of the admin area
        Given payee "Hotel X SL" with id "P1" for "account" "666" exists
        When I am logged as "owner@dediez.software"
        When I settle the account "666"
        Then the response status should be 403

    Scenario: The statement of a payout can be downloaded as a PDF
        Given payee "Hotel X SL" with id "P1" for "account" "666" exists
        When I am logged as "admin@dediez.software"
        When I settle the account "666"
        When I go to the only payout page "/statement"
        Then the response status should be 200

    Scenario: An admin downloads the SEPA remittance of the pending payouts
        Given payee "Hotel X SL" with id "P1" for "account" "666" exists
        When I am logged as "admin@dediez.software"
        When I settle the account "666"
        When I go to "/es/admin/payouts-remittance?format=sepa"
        Then the response status should be 200
        Then I see a text "pain.001.001.03"
        Then I see a text "ES9121000418450200051332"

    Scenario: An admin downloads the CSV remittance of the pending payouts
        Given payee "Hotel X SL" with id "P1" for "account" "666" exists
        When I am logged as "admin@dediez.software"
        When I settle the account "666"
        When I go to "/es/admin/payouts-remittance?format=csv"
        Then the response status should be 200
        Then I see a text "referencia;fecha_ejecucion"

    Scenario: A remittance without pending payouts is refused
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/payouts-remittance?format=sepa"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "No hay liquidaciones pendientes"

    Scenario: The beneficiaries list offers to settle the pending balance
        Given payee "Hotel X SL" with id "P1" for "account" "666" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/payees"
        Then the response status should be 200
        Then I see a text "Pendiente de liquidar"
        Then I see a text "30,00 €"
        When I click on the action "Crear liquidación"
        When I follow redirect
        Then the response status should be 200
        Then the only payout should be "draft" of "30.00"

    Scenario: The beneficiaries list does not offer to settle a payee that is not ready
        Given incomplete payee "Hotel X SL" with id "P1" for "account" "666" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/payees"
        Then the response status should be 200
        Then I see a text "30,00 €"
        Then I do not see a text "Crear liquidación"

    Scenario: The beneficiaries list does not offer to settle an already settled payee
        Given payee "Hotel X SL" with id "P1" for "account" "666" exists
        When I am logged as "admin@dediez.software"
        When I settle the account "666"
        When I go to "/es/admin/payees"
        Then the response status should be 200
        Then I do not see a text "Crear liquidación"

    Scenario: An admin marks a single payout as remitted from the list
        Given payee "Hotel X SL" with id "P1" for "account" "666" exists
        When I am logged as "admin@dediez.software"
        When I settle the account "666"
        When I go to "/es/admin/payouts"
        Then the response status should be 200
        When I click on the action "Marcar como remesada"
        When I follow redirect
        Then the response status should be 200
        Then the only payout should be "sent" of "30.00"

    Scenario: The remittance actions are grouped under a single menu
        Given payee "Hotel X SL" with id "P1" for "account" "666" exists
        When I am logged as "admin@dediez.software"
        When I settle the account "666"
        When I go to "/es/admin/payouts"
        Then the response status should be 200
        Then I see a text "Acciones"
        When I click on link "Descargar remesa SEPA"
        Then the response status should be 200
        Then I see a text "pain.001.001.03"

    Scenario: An admin downloads the CSV remittance from the remittance menu
        Given payee "Hotel X SL" with id "P1" for "account" "666" exists
        When I am logged as "admin@dediez.software"
        When I settle the account "666"
        When I go to "/es/admin/payouts"
        When I click on link "Descargar remesa CSV"
        Then the response status should be 200
        Then I see a text "referencia;fecha_ejecucion"

    Scenario: An admin marks every draft payout as remitted from the remittance menu
        Given payee "Hotel X SL" with id "P1" for "account" "666" exists
        When I am logged as "admin@dediez.software"
        When I settle the account "666"
        When I go to "/es/admin/payouts"
        When I click on link "Marcar como remesadas"
        When I follow redirect
        Then the response status should be 200
        Then the only payout should be "sent" of "30.00"

    Scenario: Remitting without a CSRF token is refused
        Given payee "Hotel X SL" with id "P1" for "account" "666" exists
        When I am logged as "admin@dediez.software"
        When I settle the account "666"
        When I go to "/es/admin/payouts-remit"
        Then the response status should be 403
        Then the only payout should be "draft" of "30.00"

    Scenario: Cancelling without a CSRF token is refused
        Given payee "Hotel X SL" with id "P1" for "account" "666" exists
        When I am logged as "admin@dediez.software"
        When I settle the account "666"
        When I go to the only payout page "/cancel"
        Then the response status should be 403
        Then the only payout should be "draft" of "30.00"

    Scenario: As an owner I cannot download the remittance
        When I am logged as "owner@dediez.software"
        When I go to "/es/admin/payouts-remittance?format=sepa"
        Then the response status should be 403
