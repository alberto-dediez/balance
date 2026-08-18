Feature:
    Ability for an owner to keep their own payment details up to date so that they can be paid

    Background:
        Given account "MyCompany" with id "666" exists
        Given account "OtherCompany" with id "999" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given user "other@dediez.software" with role "ROLE_OWNER" of account "999" exists
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists

    Scenario: An owner without payment details is asked to complete them from their balance
        When I am logged as "owner@dediez.software"
        When I go to "/es/my-balance"
        Then the response status should be 200
        Then I see a text "Sus datos de cobro están incompletos"
        When I click on link "Completar datos de cobro"
        Then the response status should be 200
        Then I see a text "Mis datos de cobro"

    Scenario: An owner registers their own payment details
        When I am logged as "owner@dediez.software"
        When I go to "/es/my-payee"
        Then the response status should be 200
        When I submit a form with "legalName:Hotel X SL|taxId:B12345678|iban:ES9121000418450200051332|holderName:Hotel X SL" and button "Guardar datos de cobro"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Datos de cobro guardados con éxito"
        Then the payee of "account" "666" should be "ready"

    Scenario: An owner completes the details an admin left incomplete
        Given incomplete payee "Hotel X SL" with id "P1" for "account" "666" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/my-payee"
        Then the response status should be 200
        When I submit a form with "legalName:Hotel X SL|taxId:B12345678|iban:ES9121000418450200051332|holderName:Hotel X SL" and button "Guardar datos de cobro"
        When I follow redirect
        Then the response status should be 200
        Then payee "P1" should be "ready"

    Scenario: An owner with complete details is not warned in their balance
        Given payee "Hotel X SL" with id "P1" for "account" "666" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/my-balance"
        Then the response status should be 200
        Then I do not see a text "Sus datos de cobro están incompletos"

    Scenario: An owner never reaches the payee of another account
        Given payee "Hotel X SL" with id "P1" for "account" "666" exists
        When I am logged as "other@dediez.software"
        When I go to "/es/my-payee"
        Then the response status should be 200
        Then I do not see a text "ES9121000418450200051332"

    Scenario: A blocked payee cannot be edited by its owner
        Given blocked payee "Hotel X SL" with id "P1" for "account" "666" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/my-payee"
        Then the response status should be 200
        Then I see a text "Sus datos de cobro están bloqueados"
        Then I do not see a text "Guardar datos de cobro"

    Scenario: The owner form never exposes the internal notes of the payee
        Given payee "Hotel X SL" with id "P1" for "account" "666" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/my-payee"
        Then the response status should be 200
        Then I do not see a text "Notas"
