Feature:
    Ability for admins to keep the fiscal and bank details of whoever gets paid

    Background:
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists

    Scenario: As an admin I register a payee with complete bank details and it becomes ready
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/payees/register"
        Then the response status should be 200
        When I submit a form with "type:account|subjectId:666|legalName:Hotel X SL|taxId:B12345678|iban:ES9121000418450200051332|holderName:Hotel X SL" and button "Crear beneficiario"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Beneficiario creado correctamente: Hotel X SL"
        Then the payee of "account" "666" should be "ready"

    Scenario: A payee without bank details stays incomplete
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/payees/register"
        When I submit a form with "type:account|subjectId:666|legalName:Hotel X SL|taxId:B12345678" and button "Crear beneficiario"
        When I follow redirect
        Then the response status should be 200
        Then the payee of "account" "666" should be "incomplete"

    Scenario: As an owner I cannot reach the payees
        When I am logged as "owner@dediez.software"
        When I go to "/es/admin/payees"
        Then the response status should be 403

    Scenario: The IBAN is masked in the list
        Given payee "Hotel X SL" with id "P1" for "account" "666" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/payees"
        Then the response status should be 200
        Then I see a text "Hotel X SL"
        Then I see a text "1332"
        Then I do not see a text "ES9121000418450200051332"
