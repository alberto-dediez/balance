Feature:
    Ability for admins to complete, correct and block the details of a payee

    Background:
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists

    Scenario: Completing the bank details of an incomplete payee makes it ready
        Given incomplete payee "Hotel Y SL" with id "P2" for "account" "666" exists
        Then payee "P2" should be "incomplete"
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/payees/edit/P2"
        Then the response status should be 200
        When I submit a form with "legalName:Hotel Y SL|taxId:B12345678|iban:ES9121000418450200051332|holderName:Hotel Y SL" and button "Guardar beneficiario"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Beneficiario actualizado con éxito"
        Then payee "P2" should be "ready"

    Scenario: Blocking and unblocking a payee
        Given payee "Hotel X SL" with id "P1" for "account" "666" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/payees/block/P1"
        When I follow redirect
        Then the response status should be 200
        Then payee "P1" should be "blocked"
        When I go to "/es/admin/payees/block/P1"
        When I follow redirect
        Then payee "P1" should be "ready"

    Scenario: Blocking and unblocking a payee from the list toggles the action
        Given payee "Hotel X SL" with id "P1" for "account" "666" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/payees"
        Then the response status should be 200
        When I click on the action "Bloquear"
        When I follow redirect
        Then the response status should be 200
        Then payee "P1" should be "blocked"
        When I click on the action "Desbloquear"
        When I follow redirect
        Then payee "P1" should be "ready"

    Scenario: The full IBAN is only visible inside the edit form
        Given payee "Hotel X SL" with id "P1" for "account" "666" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/payees/edit/P1"
        Then the response status should be 200
        Then I see a text "ES9121000418450200051332"

    Scenario: As an owner I cannot edit a payee
        Given payee "Hotel X SL" with id "P1" for "account" "666" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/admin/payees/edit/P1"
        Then the response status should be 403
