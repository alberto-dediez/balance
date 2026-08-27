Feature:
    Ability for admins to see where the money collected is and what is left to pay

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

    Scenario: The dashboard splits what was collected between its destinations
        Given payee "Hotel X SL" with id "P1" for "account" "666" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/treasury"
        Then the response status should be 200
        Then I see a text "Total cobrado"
        Then I see a text "48,40 €"
        Then I see a text "MyCompany"
        Then I see a text "Crear liquidación"

    Scenario: A beneficiary without a ready payee is flagged instead of offered for settlement
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/treasury"
        Then the response status should be 200
        Then I see a text "su ficha de cobro no está lista"
        Then I do not see a text "Crear liquidación"

    Scenario: The ledger lists every entry of the contribution
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/treasury/ledger"
        Then the response status should be 200
        Then I see a text "Neto de la aportación"
        Then I see a text "Gastos de gestión"
        Then I see a text "IVA"

    Scenario: The account detail shows the balance, the lots and the entries
        Given payee "Hotel X SL" with id "P1" for "account" "666" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/treasury/accounts/666"
        Then the response status should be 200
        Then I see a text "Devengado"
        Then I see a text "REF-1"
        Then I see a text "Hotel X SL"

    Scenario: The project detail shows its lots and the margin retained by Bioscore
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/treasury/projects/1111"
        Then the response status should be 200
        Then I see a text "Margen de Bioscore"
        Then I see a text "REF-1"

    Scenario: An admin can register a manual adjustment against a beneficiary
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/treasury/adjustments/register"
        Then the response status should be 200
        When I submit a form with "target:account/666|amount:-10|concept:Contracargo de la aportación" and button "Registrar ajuste"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Ajuste registrado correctamente"
        Then the ledger balance of account "666" should be "20.00"

    Scenario: An admin reverts an existing entry from the ledger
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/treasury/ledger"
        Then the response status should be 200
        When I click on the action "Revertir"
        Then the response status should be 200
        Then I see a text "Asiento que se revierte"
        When I submit a form with "concept:Reversión por error de imputación" and button "Registrar ajuste"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Ajuste registrado correctamente"

    Scenario: Registering an adjustment without a reason is refused
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/treasury/adjustments/register"
        Then the response status should be 200
        When I submit a form with "target:account/666|amount:-10|concept:" and button "Registrar ajuste"
        Then the response status should be 200
        Then the ledger balance of account "666" should be "30.00"

    Scenario: As an owner I cannot reach the treasury dashboard
        When I am logged as "owner@dediez.software"
        When I go to "/es/admin/treasury"
        Then the response status should be 403
