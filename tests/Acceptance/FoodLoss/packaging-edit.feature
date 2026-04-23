Feature:
    I want to make sure that the system is allow to access the details of a packaging

    Scenario: As an owner, I can access to edit my packagings
        Given account "MyCompany" with id "mycompany" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
        Given packaging "Envase 1" with id "333" of account "mycompany" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/packagings/edit/333"
        When I submit a form with "name:Nuevo Nombre" and button "Guardar"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Envase actualizado con éxito"

    Scenario: As an owner, I can't edit packagings from other account
        Given account "MyCompany" with id "mycompany" exists
        Given account "OtherCompany" with id "othercompany" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
        Given packaging "Envase 1" with id "333" of account "othercompany" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/packagings/edit/333"
        Then the response status should be 403

    Scenario: As admin I can't edit packagings
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given account "MyCompany" with id "mycompany" exists
        Given packaging "Envase 1" with id "333" of account "mycompany" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/packagings/edit/333"
        Then the response status should be 403
